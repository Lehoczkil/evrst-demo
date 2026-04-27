<?php

namespace App\Services\Onshape;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around Onshape's REST API. Authenticates with the
 * Basic-auth API-key pair from config/services.php and exposes the
 * subset of endpoints the GLB export pipeline actually needs.
 *
 * Every public method that talks to Onshape returns either a valid
 * payload or a structured failure with the actual response body —
 * not just an HTTP status code — so the export job can write a
 * useful error into glb_error for the admin to see.
 */
class Client
{
    public function __construct(
        private readonly string $accessKey,
        private readonly string $secretKey,
        private readonly string $baseUrl,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            (string) config('services.onshape.access_key', ''),
            (string) config('services.onshape.secret_key', ''),
            rtrim((string) config('services.onshape.base_url', 'https://cad.onshape.com/api/v6'), '/'),
        );
    }

    public function isConfigured(): bool
    {
        return $this->accessKey !== '' && $this->secretKey !== '';
    }

    private function http(int $timeout = 30): PendingRequest
    {
        return Http::withBasicAuth($this->accessKey, $this->secretKey)
            ->acceptJson()
            ->baseUrl($this->baseUrl)
            ->timeout($timeout);
    }

    /**
     * Cheap pre-flight check. Hits /users/sessioninfo with the API
     * keys; a 200 means the keys are valid. Used by the "Test
     * connection" admin action so the user can confirm config
     * without spending a translation request.
     *
     * @return array{ok: true, name: string} | array{ok: false, error: string}
     */
    public function ping(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'API keys not configured.'];
        }
        try {
            $resp = $this->http()->get('/users/sessioninfo');
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Network error: ' . $e->getMessage()];
        }
        if (! $resp->successful()) {
            return ['ok' => false, 'error' => $this->describeError($resp->status(), $resp->body())];
        }
        $name = (string) ($resp->json('name') ?? $resp->json('email') ?? 'unknown user');
        return ['ok' => true, 'name' => $name];
    }

    /**
     * Look up an element's type so we know which translation endpoint
     * to hit. Returns the raw element record from Onshape, or null
     * when the document / workspace / element ids don't resolve.
     *
     * @return array<string, mixed>|null
     */
    public function findElement(string $did, string $wid, string $eid): ?array
    {
        try {
            $resp = $this->http()->get("/documents/d/{$did}/w/{$wid}/elements", [
                'elementId' => $eid,
                'withThumbnails' => 'false',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Onshape findElement network error', ['error' => $e->getMessage()]);
            return null;
        }
        if (! $resp->successful()) {
            Log::warning('Onshape findElement failed', ['status' => $resp->status(), 'body' => $resp->body()]);
            return null;
        }
        $items = $resp->json();
        if (! is_array($items) || empty($items)) return null;
        return $items[0];
    }

    /**
     * Submit a GLTF translation. Picks the right endpoint based on
     * the element type — partstudios and assemblies have separate
     * /translations routes and the body params differ slightly.
     *
     * @return array{ok: true, id: string} | array{ok: false, error: string}
     */
    public function startGlbTranslation(string $did, string $wid, string $eid, string $elementType): array
    {
        $type = strtoupper($elementType);
        $endpoint = match ($type) {
            'PARTSTUDIO' => "/partstudios/d/{$did}/w/{$wid}/e/{$eid}/translations",
            'ASSEMBLY'   => "/assemblies/d/{$did}/w/{$wid}/e/{$eid}/translations",
            default      => null,
        };
        if (! $endpoint) {
            return ['ok' => false, 'error' => "Element type '{$elementType}' is not exportable to GLB (need PARTSTUDIO or ASSEMBLY)."];
        }

        // Onshape's translation params overlap mostly but differ in a
        // couple of places — keep this map narrow so we don't send a
        // partstudio param the assembly route would reject (or vice
        // versa).
        $body = [
            'formatName' => 'GLTF',
            'storeInDocument' => false,
        ];
        if ($type === 'ASSEMBLY') {
            $body += [
                'flattenAssemblies' => true,
                'includeExportIds' => false,
            ];
        }

        try {
            $resp = $this->http(30)->asJson()->post($endpoint, $body);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Network error submitting translation: ' . $e->getMessage()];
        }

        if (! $resp->successful()) {
            return ['ok' => false, 'error' => $this->describeError($resp->status(), $resp->body())];
        }

        $id = $resp->json('id') ?? $resp->json('translationId');
        if (! is_string($id) || $id === '') {
            return ['ok' => false, 'error' => 'Translation submitted but Onshape did not return an id.'];
        }
        return ['ok' => true, 'id' => $id];
    }

    /**
     * Fetch a translation's current state. State is one of ACTIVE,
     * DONE, FAILED.
     *
     * @return array<string, mixed>|null
     */
    public function getTranslation(string $translationId): ?array
    {
        try {
            $resp = $this->http()->get("/translations/{$translationId}");
        } catch (\Throwable) {
            return null;
        }
        return $resp->successful() ? $resp->json() : null;
    }

    /**
     * Stream a translation's binary result from Onshape into a local
     * file. Returns the size in bytes on success, or null on failure.
     */
    public function downloadExternalData(string $did, string $externalDataId, string $sinkPath): ?int
    {
        try {
            $resp = $this->http(120)
                ->withOptions([
                    'sink' => $sinkPath,
                    'stream' => true,
                ])
                ->get("/documents/d/{$did}/externaldata/{$externalDataId}");
        } catch (\Throwable $e) {
            @unlink($sinkPath);
            Log::warning('Onshape externaldata download failed', ['error' => $e->getMessage()]);
            return null;
        }

        if (! $resp->successful()) {
            @unlink($sinkPath);
            Log::warning('Onshape externaldata HTTP error', ['status' => $resp->status()]);
            return null;
        }
        $size = @filesize($sinkPath);
        return $size === false ? null : (int) $size;
    }

    /**
     * Turn a non-200 response into a single human-readable line. Onshape
     * usually returns `{ "message": "..." }` on errors; fall back to a
     * truncated raw body when JSON parsing fails.
     */
    private function describeError(int $status, ?string $body): string
    {
        $body = (string) $body;
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            $msg = $decoded['message']
                ?? $decoded['error']
                ?? ($decoded['moreInfoUrl'] ?? null);
            if (is_string($msg) && $msg !== '') {
                return "Onshape returned HTTP {$status}: {$msg}";
            }
        }
        $snippet = mb_substr($body, 0, 200);
        return "Onshape returned HTTP {$status}" . ($snippet !== '' ? " — {$snippet}" : '');
    }
}

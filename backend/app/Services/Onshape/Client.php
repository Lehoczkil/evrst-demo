<?php

namespace App\Services\Onshape;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around Onshape's REST API. Authenticates with the
 * Basic-auth API-key pair from config/services.php and exposes the
 * subset of endpoints the GLB export pipeline actually needs.
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

    private function http(): PendingRequest
    {
        return Http::withBasicAuth($this->accessKey, $this->secretKey)
            ->acceptJson()
            ->withHeaders(['Accept' => 'application/json;charset=UTF-8;qs=0.09'])
            ->baseUrl($this->baseUrl)
            ->timeout(30);
    }

    /**
     * Look up an element's type so we know which translation endpoint to
     * hit (PARTSTUDIO vs ASSEMBLY). Returns the raw element record from
     * Onshape, or null when the document/workspace/element ids don't
     * resolve.
     *
     * @return array<string, mixed>|null
     */
    public function findElement(string $did, string $wid, string $eid): ?array
    {
        $resp = $this->http()->get("/documents/d/{$did}/w/{$wid}/elements", [
            'elementId' => $eid,
            'withThumbnails' => 'false',
        ]);
        if (! $resp->successful()) return null;
        $items = $resp->json();
        if (! is_array($items) || empty($items)) return null;
        return $items[0];
    }

    /**
     * Submit a GLTF translation. Onshape exposes per-element-type
     * endpoints; we pick PARTSTUDIO or ASSEMBLY based on the element
     * type and POST the same payload.
     *
     * @return array{ok: true, id: string} | array{ok: false, error: string}
     */
    public function startGlbTranslation(string $did, string $wid, string $eid, string $elementType): array
    {
        $endpoint = match (strtoupper($elementType)) {
            'PARTSTUDIO' => "/partstudios/d/{$did}/w/{$wid}/e/{$eid}/translations",
            'ASSEMBLY'   => "/assemblies/d/{$did}/w/{$wid}/e/{$eid}/translations",
            default      => null,
        };
        if (! $endpoint) {
            return ['ok' => false, 'error' => "Unsupported element type: {$elementType}"];
        }

        $resp = $this->http()
            ->asJson()
            ->post($endpoint, [
                'formatName' => 'GLTF',
                'storeInDocument' => false,
                // Binary GLB is what Three.js' GLTFLoader prefers — one file,
                // self-contained, with embedded textures + buffers.
                'flattenAssemblies' => true,
                'yAxisIsUp' => true,
            ]);

        if (! $resp->successful()) {
            return ['ok' => false, 'error' => 'Translation request failed: HTTP ' . $resp->status()];
        }

        $id = $resp->json('id') ?? $resp->json('translationId');
        if (! is_string($id) || $id === '') {
            return ['ok' => false, 'error' => 'Translation submitted but no id was returned'];
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
        $resp = $this->http()->get("/translations/{$translationId}");
        return $resp->successful() ? $resp->json() : null;
    }

    /**
     * Stream a translation's binary result from Onshape into a local
     * file. Returns the size in bytes on success, or null on failure.
     */
    public function downloadExternalData(string $did, string $externalDataId, string $sinkPath): ?int
    {
        $resp = $this->http()
            ->withOptions([
                'sink' => $sinkPath,
                'stream' => true,
            ])
            ->get("/documents/d/{$did}/externaldata/{$externalDataId}");

        if (! $resp->successful()) {
            @unlink($sinkPath);
            return null;
        }
        $size = @filesize($sinkPath);
        return $size === false ? null : (int) $size;
    }
}

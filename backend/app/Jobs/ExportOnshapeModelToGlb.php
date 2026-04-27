<?php

namespace App\Jobs;

use App\Models\OnshapeModel;
use App\Services\Onshape\Client as OnshapeClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Export a single OnshapeModel's CAD document to a GLB on the public
 * disk. Polls Onshape's translation endpoint until the job is DONE
 * (or FAILED), streams the result onto storage/app/public/onshape/,
 * and updates the OnshapeModel with the new path / size / timestamp.
 */
class ExportOnshapeModelToGlb implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // We do our own polling; let the job itself fail loudly.

    public int $timeout = 240;

    public function __construct(public int $modelId)
    {
    }

    public function handle(): void
    {
        /** @var OnshapeModel|null $model */
        $model = OnshapeModel::find($this->modelId);
        if (! $model) return;

        $client = OnshapeClient::fromConfig();
        if (! $client->isConfigured()) {
            $this->fail($model, 'Onshape API keys are not configured (set ONSHAPE_ACCESS_KEY + ONSHAPE_SECRET_KEY).');
            return;
        }
        if (! $model->document_id || ! $model->workspace_id || ! $model->element_id) {
            $this->fail($model, 'Document / workspace / element ID is missing on the model row.');
            return;
        }

        $model->forceFill(['glb_status' => OnshapeModel::GLB_RUNNING, 'glb_error' => null])->save();

        // 1. Resolve element type so we hit the right translation endpoint.
        $element = $client->findElement($model->document_id, $model->workspace_id, $model->element_id);
        $elementType = is_array($element) ? (string) ($element['elementType'] ?? '') : '';
        if ($elementType === '') {
            $this->fail($model, 'Onshape returned no element record — verify the share URL points at a valid Part Studio or Assembly the API keys can read.');
            return;
        }

        // 2. Submit the translation.
        $start = $client->startGlbTranslation(
            $model->document_id, $model->workspace_id, $model->element_id, $elementType,
        );
        if (! ($start['ok'] ?? false)) {
            $this->fail($model, $start['error'] ?? 'Translation request failed.');
            return;
        }
        $translationId = $start['id'];

        // 3. Poll until DONE or FAILED. Cap at ~3 minutes wall-clock —
        //    big assemblies regularly take 30-90s to translate. Backs off
        //    after the first burst so we don't hammer the API for a slow
        //    job.
        $deadline = microtime(true) + 180;
        $externalDataId = null;
        $pollCount = 0;
        while (microtime(true) < $deadline) {
            $pollCount++;
            $tx = $client->getTranslation($translationId);
            $state = is_array($tx) ? (string) ($tx['requestState'] ?? $tx['state'] ?? '') : '';
            if ($state === 'DONE') {
                $ids = $tx['resultExternalDataIds'] ?? [];
                $externalDataId = is_array($ids) ? ($ids[0] ?? null) : null;
                break;
            }
            if ($state === 'FAILED') {
                $reason = is_array($tx) ? (string) ($tx['failureReason'] ?? 'Translation failed') : 'Translation failed';
                $this->fail($model, "Onshape translation failed: {$reason}");
                return;
            }
            // 1.5s for the first 6 polls, then 3s thereafter.
            usleep($pollCount <= 6 ? 1_500_000 : 3_000_000);
        }

        if (! $externalDataId) {
            $this->fail($model, 'Translation timed out after 3 minutes. The document may be too complex to translate inline; try splitting it or running the export off-hours.');
            return;
        }

        // 4. Stream the result onto the public disk.
        $relativePath = 'onshape/' . Str::ulid() . '.glb';
        $diskRoot = Storage::disk('public')->path('');
        if (! is_dir($diskRoot . 'onshape')) @mkdir($diskRoot . 'onshape', 0755, true);
        $absolutePath = $diskRoot . $relativePath;

        $size = $client->downloadExternalData($model->document_id, $externalDataId, $absolutePath);
        if ($size === null) {
            $this->fail($model, 'Onshape returned no payload for the translated GLB.');
            return;
        }

        // 5. Swap in the new file; drop the previous one if any.
        $previousPath = $model->glb_path;
        $previousDisk = $model->glb_disk;
        $model->forceFill([
            'glb_disk' => 'public',
            'glb_path' => $relativePath,
            'glb_size' => $size,
            'glb_exported_at' => now(),
            'glb_status' => OnshapeModel::GLB_IDLE,
            'glb_error' => null,
        ])->save();
        if ($previousPath && $previousPath !== $relativePath) {
            try {
                Storage::disk($previousDisk ?: 'public')->delete($previousPath);
            } catch (\Throwable) {
                // best-effort
            }
        }
    }

    private function fail(OnshapeModel $model, string $reason): void
    {
        Log::warning('Onshape export failed', ['model_id' => $model->id, 'reason' => $reason]);
        $model->forceFill([
            'glb_status' => OnshapeModel::GLB_FAILED,
            'glb_error' => mb_substr($reason, 0, 500),
        ])->save();
    }
}

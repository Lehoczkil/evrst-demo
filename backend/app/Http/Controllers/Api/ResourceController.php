<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResourceResource;
use App\Models\Resource as ResourceModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class ResourceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ResourceModel::query()
            ->orderBy('position')
            ->orderBy('created_at');

        if ($collectionId = $request->query('collectionId')) {
            $query->where('collection_id', $collectionId);
        }

        if ($where = $request->query('where')) {
            $this->applyWhere($query, (array) $where);
        }

        // ?past=true filters rows whose start_at / end_at column is in the
        // past. The columns were promoted out of `payload` (see migration
        // 2026_05_03_000004) and are indexed, so this is a single B-tree
        // scan instead of the previous full-table json_extract walk.
        // Legacy events with only a free-text payload.date (e.g.
        // "September 2025") still slip through — that field isn't
        // datetime-comparable so we can't do better than a pass-through.
        if ($request->boolean('past')) {
            $now = now();
            $query->where(function ($q) use ($now) {
                $q->where('end_at', '<=', $now)
                  ->orWhere(function ($q2) use ($now) {
                      $q2->whereNull('end_at')
                         ->where('start_at', '<=', $now);
                  })
                  ->orWhere(function ($q3) {
                      $q3->whereNull('start_at')->whereNull('end_at');
                  });
            });
        }

        if ($this->shouldIncludeObjects($request)) {
            $query->with('objects');
        }

        $resources = $query->get();

        return ResourceResource::collection($resources);
    }

    public function show(Request $request, string $id): ResourceResource
    {
        $query = ResourceModel::query()->whereKey($id);

        if ($this->shouldIncludeObjects($request)) {
            $query->with('objects');
        }

        $resource = $query->firstOrFail();

        return new ResourceResource($resource);
    }

    private function shouldIncludeObjects(Request $request): bool
    {
        $include = $request->query('include');
        if (! $include) return false;
        if (is_string($include)) {
            return in_array('objects', array_map('trim', explode(',', $include)), true);
        }
        return is_array($include) && in_array('objects', $include, true);
    }

    /**
     * Translate a Payload-style nested where clause into a JSON predicate.
     * Supports the shape used by the dynamic-page loader:
     *   where: { payload: { path: ['name'], equals: 'home' } }
     */
    private function applyWhere($query, array $where): void
    {
        if (Arr::has($where, 'payload.path') && Arr::has($where, 'payload.equals')) {
            $path = (array) Arr::get($where, 'payload.path');
            $equals = Arr::get($where, 'payload.equals');
            $jsonKey = 'payload->' . implode('->', $path);
            $query->where($jsonKey, $equals);
        }
    }
}

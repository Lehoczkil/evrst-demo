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

        // ?past=true filters rows whose payload.start_at / .end_at is in
        // the past. Legacy events with only a free-text payload.date
        // (e.g. "September 2025") are kept and assumed past — that field
        // isn't datetime-comparable so we can't do better than a pass-through.
        if ($request->boolean('past')) {
            $now = now()->toDateTimeString();
            $query->where(function ($q) use ($now) {
                $q->where('payload->end_at', '<=', $now)
                  ->orWhere(function ($q2) use ($now) {
                      $q2->whereNull('payload->end_at')
                         ->where('payload->start_at', '<=', $now);
                  })
                  ->orWhere(function ($q3) {
                      // Legacy rows with no start_at fall through.
                      $q3->whereNull('payload->start_at')
                         ->whereNull('payload->end_at');
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

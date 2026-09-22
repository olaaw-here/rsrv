<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Resource\StoreResourceRequest;
use App\Http\Requests\Resource\UpdateResourceRequest;
use App\Http\Resources\ResourceResource;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProviderResourceController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $resources = $request->user()->providerProfile
            ->resources()
            ->with('category')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => ResourceResource::collection($resources->items()),
            'meta' => [
                'current_page' => $resources->currentPage(),
                'last_page'    => $resources->lastPage(),
                'total'        => $resources->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreResourceRequest $request): JsonResponse
    {
        $resource = $request->user()->providerProfile->resources()->create([
            ...$request->safe()->except('images'),
            'status' => 'draft', // resource baru default draft, provider aktifkan manual
        ]);

        foreach ($request->safe()->input('images', []) as $index => $url) {
            $resource->images()->create(['url' => $url, 'sort_order' => $index]);
        }

        return response()->json(new ResourceResource($resource->load('images')), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Resource $resource): JsonResponse
    {
        $this->authorizeOwnership($resource);

        $resource->load(['category', 'images', 'operationalHours']);

        return response()->json(new ResourceResource($resource));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResourceRequest $request, Resource $resource): JsonResponse
    {
        $resource->update($request->validated());

        return response()->json(new ResourceResource($resource));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Resource $resource): JsonResponse
    {
        $this->authorizeOwnership($resource);

       if ($resource->bookings()->exists()) {
            $resource->update(['status' => 'inactive']);

            return response()->json(['message' => 'Resource dinonaktifkan (memiliki riwayat booking).']);
        }

        $resource->delete();

        return response()->json(['message' => 'Resource berhasil dihapus.']);
    }

    protected function authorizeOwnership(Resource $resource): void
    {
        $providerProfile = request()->user()->providerProfile;

        if (! $providerProfile || $resource->provider_id !== $providerProfile->id) {
            abort(403, 'Resource ini bukan milik Anda.');
        }
    }
}

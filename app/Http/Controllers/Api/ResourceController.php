<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResourceResource;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ResourceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Resource::query()
            ->active()
            ->with(['category', 'provider'])
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('city'), fn ($q) => $q->whereHas('provider', fn ($q) => $q->where('city', 'like', '%' . $request->city . '%')))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->search . '%'));

        $resources = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'data'  => ResourceResource::collection($resources->items()),
            'meta'  => [
                'current_page'  => $resources->currentPage(),
                'last_page'     => $resources->lastPage(),
                'total'         => $resources->total(),
            ],
        ]);
    }

    public function show(Resource $resource): JsonResponse
    {
        abort_unless($resource->status === 'active', 404);

        $resource->load(['category', 'provider', 'images', 'operationalHours']);
        return response()->json(new ResourceResource($resource));
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProviderApprovalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $providers = ProviderProfile::query()
            ->with(['user:id,name,email,phone'])
            ->withCount('resources')
            ->when($request->filled('status') && in_array($request->status, ['pending', 'active', 'suspended'], true),
                fn ($query) => $query->where('status', $request->status))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 WHEN 'suspended' THEN 2 ELSE 3 END")
            ->latest()
            ->paginate(min(max($request->integer('per_page', 20), 1), 100));

        return response()->json([
            'data' => $providers->getCollection()->map(fn (ProviderProfile $profile) => [
                'id' => $profile->id,
                'business_name' => $profile->business_name,
                'description' => $profile->description,
                'address' => $profile->address,
                'city' => $profile->city,
                'status' => $profile->status,
                'rating_avg' => (float) $profile->rating_avg,
                'total_reviews' => (int) $profile->total_reviews,
                'resources_count' => (int) $profile->resources_count,
                'user' => $profile->user ? [
                    'id' => $profile->user->id,
                    'name' => $profile->user->name,
                    'email' => $profile->user->email,
                    'phone' => $profile->user->phone,
                ] : null,
                'created_at' => $profile->created_at,
            ]),
            'meta' => [
                'current_page' => $providers->currentPage(),
                'last_page' => $providers->lastPage(),
                'total' => $providers->total(),
            ],
        ]);
    }

    public function update(Request $request, ProviderProfile $providerProfile): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'active', 'suspended'])],
        ]);

        $providerProfile->update(['status' => $validated['status']]);
        $providerProfile->load('user:id,name,email,phone')->loadCount('resources');

        return response()->json([
            'message' => 'Status provider berhasil diperbarui.',
            'data' => [
                'id' => $providerProfile->id,
                'business_name' => $providerProfile->business_name,
                'status' => $providerProfile->status,
                'resources_count' => (int) $providerProfile->resources_count,
                'user' => $providerProfile->user ? [
                    'id' => $providerProfile->user->id,
                    'name' => $providerProfile->user->name,
                    'email' => $providerProfile->user->email,
                    'phone' => $providerProfile->user->phone,
                ] : null,
            ],
        ]);
    }
}

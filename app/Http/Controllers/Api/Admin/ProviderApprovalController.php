<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProviderApprovalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $providers = ProviderProfile::with('user')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => $providers->items(),
            'meta' => [
                'current_page' => $providers->currentPage(),
                'last_page' => $providers->lastPage(),
                'total' => $providers->total(),
            ],
        ]);
    }

    public function update(Request $request, ProviderProfile $providerProfile, NotificationService $notifications): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'active', 'suspended'])],
        ]);

        $providerProfile->update(['status' => $validated['status']]);

        $title = match ($validated['status']) {
            'active' => 'Provider disetujui',
            'suspended' => 'Provider ditangguhkan',
            default => 'Status provider diperbarui',
        };

        $message = match ($validated['status']) {
            'active' => 'Akun provider Anda telah disetujui dan fitur provider sekarang dapat digunakan.',
            'suspended' => 'Akun provider Anda sedang ditangguhkan. Silakan hubungi admin untuk informasi lebih lanjut.',
            default => 'Status pengajuan provider Anda kembali menjadi pending.',
        };

        $notifications->send($providerProfile->user, 'provider_status', $title, $message);

        return response()->json($providerProfile->fresh('user'));
    }
}

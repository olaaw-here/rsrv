<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\UpdateProviderProfileRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\ProviderProfileResource;
use App\Http\Resources\ResourceResource;
use App\Models\ProviderProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ProviderProfileController — CRUD profil provider oleh provider sendiri.
 *
 * Endpoint ini diakses oleh user yang sudah terautentikasi dan berstatus
 * role=provider. Profil dibuat otomatis saat registrasi; controller ini
 * menangani operasi baca & perbarui saja (create via /register, delete
 * tidak didukung untuk menjaga integritas data historis).
 */
class ProviderProfileController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // READ
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/provider/profile
     * Mengembalikan profil provider milik user yang sedang login.
     */
    public function show(Request $request): JsonResponse
    {
        $profile = $this->resolveProfile($request);

        $profile->load('user')->loadCount('resources');

        return response()->json(new ProviderProfileResource($profile));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * PUT /api/provider/profile
     * Memperbarui informasi profil provider (sebagian atau seluruh field).
     */
    public function update(UpdateProviderProfileRequest $request): JsonResponse
    {
        $profile = $this->resolveProfile($request);

        $profile->update($request->validated());
        $profile->load('user')->loadCount('resources');

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data'    => new ProviderProfileResource($profile),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STATISTIK & RINGKASAN
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/provider/profile/resources
     * Daftar semua resource milik provider ini (tanpa paginasi penuh — cocok
     * untuk dropdown / summary di dashboard).
     */
    public function resources(Request $request): JsonResponse
    {
        $profile = $this->resolveProfile($request);

        $resources = $profile->resources()
            ->with(['category', 'images'])
            ->withCount('bookings')
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->status)
            )
            ->latest()
            ->paginate($this->perPage($request, 15));

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
     * GET /api/provider/profile/bookings
     * Semua booking yang masuk ke resource provider ini.
     */
    public function bookings(Request $request): JsonResponse
    {
        $profile     = $this->resolveProfile($request);
        $resourceIds = $profile->resources()->pluck('id');

        $bookings = \App\Models\Booking::whereIn('resource_id', $resourceIds)
            ->with(['resource', 'user:id,name,email,phone', 'bookingSlots.timeSlot'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('resource_id'), fn ($q) => $q->where('resource_id', $request->resource_id))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate($this->perPage($request, 15));

        return response()->json([
            'data' => BookingResource::collection($bookings->items()),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'total'        => $bookings->total(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPER
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Ambil ProviderProfile yang dimiliki user yang sedang login.
     * Abort 403 jika profil belum ada (user customer, atau belum mendaftar).
     */
    protected function resolveProfile(Request $request): ProviderProfile
    {
        $profile = $request->user()?->providerProfile;

        if (! $profile) {
            abort(403, 'Akun Anda belum memiliki profil provider.');
        }

        return $profile;
    }
}

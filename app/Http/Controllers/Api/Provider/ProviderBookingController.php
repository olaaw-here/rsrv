<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * ProviderBookingController — Manajemen booking yang masuk ke resource provider.
 *
 * Provider dapat:
 * - Melihat daftar booking (dengan filter status, resource, tanggal)
 * - Melihat detail satu booking
 * - Mengkonfirmasi booking manual (jika alur pembayaran offline/manual)
 * - Menandai booking sebagai completed
 * - Menolak booking (dengan alasan)
 */
class ProviderBookingController extends Controller
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LIST
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/provider/bookings
     * Daftar semua booking pada resource milik provider.
     *
     * Query params:
     *   - status        : filter status (pending_payment|confirmed|completed|cancelled|expired)
     *   - resource_id   : filter berdasarkan resource
     *   - from / to     : filter rentang tanggal created_at (Y-m-d)
     *   - per_page      : jumlah per halaman (default 15)
     */
    public function index(Request $request): JsonResponse
    {
        $profile     = $this->resolveProfile($request);
        $resourceIds = $profile->resources()->pluck('id');

        $bookings = Booking::whereIn('resource_id', $resourceIds)
            ->with(['resource.category', 'resource.provider', 'user:id,name,email,phone', 'bookingSlots.timeSlot', 'payments'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('resource_id'), fn ($q) => $q->where('resource_id', $request->resource_id))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->latest()
            ->paginate($request->integer('per_page', 15));

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
    // SHOW
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/provider/bookings/{booking}
     * Detail satu booking (hanya booking milik resource provider ini).
     */
    public function show(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeOwnership($request, $booking);

        $booking->load(['resource.category', 'resource.provider', 'user:id,name,email,phone', 'bookingSlots.timeSlot', 'payments', 'review']);

        return response()->json(new BookingResource($booking));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AKSI STATUS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/provider/bookings/{booking}/confirm
     * Konfirmasi manual booking (misal: pembayaran cash / transfer manual).
     * Hanya berlaku untuk booking berstatus pending_payment.
     */
    public function confirm(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeOwnership($request, $booking);

        if ($booking->status !== 'pending_payment') {
            return response()->json([
                'message' => 'Booking hanya dapat dikonfirmasi saat berstatus pending_payment.',
            ], 422);
        }

        $booking->confirm();

        $this->notificationService->send(
            $booking->user,
            'booking_confirmed',
            'Booking Dikonfirmasi',
            "Booking {$booking->booking_code} telah dikonfirmasi oleh provider."
        );

        $booking->load(['resource.category', 'resource.provider', 'bookingSlots.timeSlot']);

        return response()->json([
            'message' => 'Booking berhasil dikonfirmasi.',
            'data'    => new BookingResource($booking),
        ]);
    }

    /**
     * POST /api/provider/bookings/{booking}/complete
     * Tandai booking sebagai selesai (completed).
     * Hanya berlaku untuk booking berstatus confirmed.
     */
    public function complete(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeOwnership($request, $booking);

        if ($booking->status !== 'confirmed') {
            return response()->json([
                'message' => 'Booking harus berstatus confirmed sebelum dapat diselesaikan.',
            ], 422);
        }

        $booking->update(['status' => 'completed']);

        $this->notificationService->send(
            $booking->user,
            'booking_completed',
            'Booking Selesai',
            "Booking {$booking->booking_code} telah selesai. Terima kasih!"
        );

        $booking->load(['resource.category', 'resource.provider', 'bookingSlots.timeSlot']);

        return response()->json([
            'message' => 'Booking ditandai sebagai selesai.',
            'data'    => new BookingResource($booking),
        ]);
    }

    /**
     * POST /api/provider/bookings/{booking}/reject
     * Tolak booking (provider memutuskan tidak dapat melayani).
     * Hanya berlaku untuk booking berstatus pending_payment.
     * Slot yang ditahan akan dilepas kembali menjadi available.
     */
    public function reject(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeOwnership($request, $booking);

        if ($booking->status !== 'pending_payment') {
            return response()->json([
                'message' => 'Hanya booking berstatus pending_payment yang dapat ditolak.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        // Lepas slot & set status cancelled
        $booking->releaseSlots('cancelled');

        // Batalkan payment yang pending jika ada
        $booking->payments()->where('status', 'pending')->update(['status' => 'cancel']);

        $reason = $validated['reason'];
        $booking->update(['cancellation_reason' => $reason]);

        $this->notificationService->send(
            $booking->user,
            'booking_rejected',
            'Booking Ditolak',
            "Booking {$booking->booking_code} ditolak: {$reason}"
        );

        return response()->json([
            'message' => 'Booking berhasil ditolak.',
            'data'    => new BookingResource($booking->fresh()),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Pastikan booking ini milik resource provider yang login.
     */
    protected function authorizeOwnership(Request $request, Booking $booking): void
    {
        $profile = $request->user()?->providerProfile;

        if (! $profile) {
            abort(403, 'Akun belum memiliki profil provider.');
        }

        $resourceIds = $profile->resources()->pluck('id');

        if (! $resourceIds->contains($booking->resource_id)) {
            abort(403, 'Booking ini bukan milik resource Anda.');
        }
    }

    /**
     * Ambil ProviderProfile user yang login; abort jika tidak ada.
     */
    protected function resolveProfile(Request $request): \App\Models\ProviderProfile
    {
        $profile = $request->user()?->providerProfile;

        if (! $profile) {
            abort(403, 'Akun belum memiliki profil provider.');
        }

        return $profile;
    }
}

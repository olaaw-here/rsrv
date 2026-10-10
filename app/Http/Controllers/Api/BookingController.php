<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdateBookingNotesRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
        protected PaymentService $paymentService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $bookings = $request->user()->bookings()
            ->with(['resource.category', 'resource.provider', 'bookingSlots.timeSlot', 'review', 'payments'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($this->perPage($request, 10));

        return response()->json([
            'data'  => BookingResource::collection($bookings->items()),
            'meta'  => [
                'current_page'  => $bookings->currentPage(),
                'last_page'     => $bookings->lastPage(),
                'total'         => $bookings->total(),
            ],
        ]);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        try {
            $result = $this->bookingService->create($request->user(), $request->validated());
        } catch (PaymentGatewayException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'booking'     => new BookingResource($result['booking']),
            'snap_token'  => $result['payment']->snap_token,
            'payment_url' => $result['payment']->payment_url,
        ], 201);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeOwnership($request, $booking);

        $booking->load(['resource.category', 'resource.provider', 'bookingSlots.timeSlot', 'review', 'payments']);

        return response()->json(new BookingResource($booking));
    }

    public function updateNotes(UpdateBookingNotesRequest $request, Booking $booking): JsonResponse
    {
        $this->authorizeOwnership($request, $booking);

        if (! in_array($booking->status, ['pending_payment', 'confirmed'], true)) {
            return response()->json(['message' => 'Catatan hanya dapat diubah pada booking yang masih aktif.'], 422);
        }

        $booking->update(['customer_notes' => $request->validated('customer_notes')]);

        $booking->load(['resource.category', 'resource.provider', 'bookingSlots.timeSlot', 'review', 'payments']);

        return response()->json(new BookingResource($booking));
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeOwnership($request, $booking);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $booking = $this->bookingService->cancel($booking, $validated['reason']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        $booking->load(['resource.category', 'resource.provider', 'bookingSlots.timeSlot', 'review', 'payments']);

        return response()->json(new BookingResource($booking));
    }

    public function initiatePayment(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeOwnership($request, $booking);

        if ($booking->status !== 'pending_payment') {
            return response()->json(['message' => 'Booking tidak dalam status menunggu pembayaran.'], 422);
        }

        try {
            $payment = $this->paymentService->initiateForBooking($booking);
        } catch (PaymentGatewayException $e) {
            report($e);
            return response()->json(['message' => 'Gagal membuat transaksi pembayaran. Silakan coba lagi.'], 502);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'snap_token' => $payment->snap_token,
            'payment_url' => $payment->payment_url,
        ]);
    }

    protected function authorizeOwnership(Request $request, Booking $booking): void
    {
        if ($request->user()->id !== $booking->user_id) {
            abort(403, 'Anda tidak memiliki akses ke booking ini.');
        }
    }
}

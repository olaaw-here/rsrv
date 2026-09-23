<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
            ->with(['resource', 'bookingSlots.timeSlot'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->integer('per_page', 10));

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
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'booking' => new BookingResource($result['booking']),
            'snap_token' => $result['payment']->snap_token,
            'payment_url' => $result['payment']->payment_url,
        ], 201);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeOwnership($request, $booking);

        $booking->load(['resource', 'bookingSlots.timeSlot']);

        return response()->json(new BookingResource($booking));
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeOwnership($request, $booking);

        try {
            $this->bookingService->cancel($booking);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json(new BookingResource($booking));
    }

    public function initiatePayment(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeOwnership($request, $booking);

        if ($booking->status !== 'pending_payment') {
            return response()->json(['message' => 'Booking tidak dalam status menunggu pembayaran.'], 422);
        }

        $payment = $this->paymentService->initiateForBooking($booking);

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

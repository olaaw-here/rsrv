<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class RefundController extends Controller
{
    public function __construct(protected RefundService $refundService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $refunds = Refund::with(['payment.booking.user', 'payment.booking.resource', 'processedBy'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => $refunds->items(),
            'meta' => [
                'current_page' => $refunds->currentPage(),
                'last_page' => $refunds->lastPage(),
                'total' => $refunds->total(),
            ],
        ]);
    }

    public function requestRefund(Request $request, Payment $payment): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $refund = $this->refundService->request(
                $payment,
                (float) $data['amount'],
                $data['reason'] ?? null,
                $request->user()
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Midtrans menolak atau gagal memproses request refund.'], 502);
        }

        return response()->json(['message' => 'Refund berhasil diajukan ke payment gateway.', 'data' => $refund->load('payment')], 201);
    }

    public function process(Request $request, Refund $refund): JsonResponse
    {
        try {
            $refund = $this->refundService->markProcessed($refund, $request->user());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Refund ditandai selesai.', 'data' => $refund]);
    }

    public function reject(Request $request, Refund $refund): JsonResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        try {
            $refund = $this->refundService->reject($refund, $request->user(), $data['reason'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Refund ditolak.', 'data' => $refund]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    public function handleMidtrans(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (! $this->paymentService->verifySignature($payload)) {
            Log::warning('Webhook Midtrans signature verification failed', [
                'order_id' => $payload['order_id'] ?? null,
            ]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $this->paymentService->handleNotification($payload);
        return response()->json(['message' => 'Webhook received successfully']);
    }
}

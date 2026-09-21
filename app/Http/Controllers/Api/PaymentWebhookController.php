<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    public function HandleMidtrans(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (! $this->paymentService->verifyMidtransSignature($payload)) {
            Log::warning('Webhook Midtrans signature verification failed', ['order_id' => $payload['order_id'] ?? null,]);

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $this->paymentService->handleNotification($payload);

        return response()->json(['message' => 'Webhook received successfully']);
    }
}

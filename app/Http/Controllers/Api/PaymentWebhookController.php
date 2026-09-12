<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentWebhookController extends Controller
{
    public function HandleMidtrans(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }
}

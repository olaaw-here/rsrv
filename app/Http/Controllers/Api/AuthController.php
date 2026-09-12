<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }

    public function login(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }

    public function logout(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Not implemented yet'], 501);
    }
}

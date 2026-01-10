<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->authService->registerUser($request->validated());
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ]
            ], 201);
        } catch (Throwable $exception) {
            Log::error('[AuthController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $user = $this->authService->loginUser($request->email, $request->password);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Неверные учетные данные'
                ], 401);
            }

            $token = $user->tokens->first();

            if ($token && $token->expires_at && $token->expires_at > now()) {
                $token = $token->token;
            } else {
                $token = $user->createToken('auth-token')->plainTextToken;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ]
            ]);
        } catch (Throwable $exception) {
            Log::error('[AuthController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    public function logout()
    {
        try {
            $this->authService->logoutUser();

            return response()->json(['success' => true]);
        } catch (Throwable $exception) {
            Log::error('[AuthController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    public function user(Request $request): JsonResponse
    {
        try {
            $user = $this->authService->getAuthenticatedUser($request->bearerToken());

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                ]
            ]);
        } catch (Throwable $exception) {
            Log::error('[AuthController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }
}



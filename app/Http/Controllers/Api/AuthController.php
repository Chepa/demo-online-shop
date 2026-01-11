<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
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
            return $this->handleException($exception, 'AuthController');
        }
    }

    public function login(LoginRequest $request): JsonResponse
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
            return $this->handleException($exception, 'AuthController');
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $this->authService->logoutUser();

            return response()->json(['success' => true]);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'AuthController');
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
            return $this->handleException($exception, 'AuthController');
        }
    }
}



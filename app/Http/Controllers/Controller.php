<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;
use Throwable;

#[OA\Info(
    version: "1.0.0",
    title: "Online Shop API",
    description: "API для интернет-магазина"
)]
#[OA\Server(
    url: "/api",
    description: "API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    name: "Authorization",
    in: "header",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
abstract class Controller
{
    /**
     * Обработка исключений и возврат стандартизированного ответа об ошибке
     */
    protected function handleException(Throwable $exception, string $context = ''): JsonResponse
    {
        $message = $exception->getMessage();
        $contextMessage = $context ? "[{$context}]: " : '';
        
        Log::error($contextMessage . $message, [
            'exception' => get_class($exception),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Для production окружения не показываем детали ошибки
        $errorMessage = app()->environment('production') 
            ? 'Произошла ошибка при обработке запроса' 
            : $message;

        return response()->json([
            'success' => false,
            'message' => $errorMessage,
        ], $this->getStatusCodeFromException($exception));
    }

    /**
     * Получить HTTP статус код из исключения
     */
    protected function getStatusCodeFromException(Throwable $exception): int
    {
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }

        if (method_exists($exception, 'getCode') && $exception->getCode() >= 400 && $exception->getCode() < 600) {
            return (int) $exception->getCode();
        }

        return 500;
    }
}

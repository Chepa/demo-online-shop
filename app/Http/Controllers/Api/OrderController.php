<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStatusRequest;
use App\Http\Requests\Shop\CreateOrder;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = $request->integer('per_page', 10);
            $orders = $this->orderService->getUserOrders($user, $perPage);

            return response()->json($orders);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'OrderController');
        }
    }

    public function store(CreateOrder $request): JsonResponse
    {
        try {
            $user = $request->user();
            $order = $this->orderService->createOrderFromCart($user, $request->validated());

            return response()->json($order, 201);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'OrderController');
        }
    }

    public function adminIndex(Request $request): JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 20);
            $orders = $this->orderService->getAllOrders($perPage);

            return response()->json($orders);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'OrderController');
        }
    }

    public function updateStatus(UpdateStatusRequest $request, Order $order): JsonResponse
    {
        try {
            $order = $this->orderService->updateOrderStatus($order, $request->get('status'));

            return response()->json($order);
        } catch (Throwable $exception) {
            return $this->handleException($exception, 'OrderController');
        }
    }
}

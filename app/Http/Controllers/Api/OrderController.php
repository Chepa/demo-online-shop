<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStatusRequest;
use App\Http\Requests\Shop\CreateOrder;
use App\Models\Order;
use App\Services\Order\OrderService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {
    }

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $orders = $this->orderService->getUserOrders($user);

            return response()->json($orders);
        } catch (Throwable $exception) {
            Log::error('[OrderController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    /**
     * @throws Exception
     */
    public function store(CreateOrder $request)
    {
        try {
            $user = $request->user();
            $order = $this->orderService->createOrderFromCart($user, $request->validated());

            return response()->json($order, 201);
        } catch (Throwable $exception) {
            Log::error('[OrderController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    public function adminIndex()
    {
        try {
            $orders = $this->orderService->getAllOrders();

            return response()->json($orders);
        } catch (Throwable $exception) {
            Log::error('[OrderController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }

    public function updateStatus(UpdateStatusRequest $request, Order $order)
    {
        try {
            $order = $this->orderService->updateOrderStatus($order, $request->get('status'));

            return response()->json($order);
        } catch (Throwable $exception) {
            Log::error('[OrderController]: ' . $exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }
}

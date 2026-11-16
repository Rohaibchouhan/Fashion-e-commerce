<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->getCustomerOrders(
            $request->user(),
            $request->query('status')
        );

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, string $orderIdentifier): JsonResponse
    {
        try {
            $order = $this->orderService->getOrderForCustomer($request->user(), $orderIdentifier);

            return response()->json([
                'success' => true,
                'data' => new OrderResource($order),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    public function track(Request $request): JsonResponse
    {
        $request->validate([
            'order_number' => 'required|string|min:4',
            'email_or_zip' => 'required|string|min:3',
        ]);

        try {
            $order = $this->orderService->trackOrder($request->order_number, $request->email_or_zip);

            return response()->json([
                'success' => true,
                'data' => new OrderResource($order),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    public function poll(Request $request, string $orderNumber): JsonResponse
    {
        $emailOrZip = $request->query('email_or_zip') ?? $request->header('X-Verify-Email-Or-Zip');

        if ($request->user()) {
            try {
                $order = $this->orderService->getOrderForCustomer($request->user(), $orderNumber);
                return response()->json([
                    'success' => true,
                    'data' => [
                        'status' => $order->status->value,
                        'label' => $order->status->label(),
                        'tracking_number' => $order->tracking_number,
                        'carrier' => $order->carrier,
                        'timeline' => $order->timeline,
                        'updated_at' => $order->updated_at->toISOString(),
                    ],
                ]);
            } catch (Exception $e) {
                // fall through to public verification
            }
        }

        if (!$emailOrZip) {
            return response()->json([
                'success' => false,
                'message' => 'Verification credential (email_or_zip) is required to poll order status.',
            ], 403);
        }

        try {
            $order = $this->orderService->trackOrder($orderNumber, $emailOrZip);

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $order->status->value,
                    'label' => $order->status->label(),
                    'tracking_number' => $order->tracking_number,
                    'carrier' => $order->carrier,
                    'timeline' => $order->timeline,
                    'updated_at' => $order->updated_at->toISOString(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    public function cancel(Request $request, string $orderNumber): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $order = $this->orderService->getOrderForCustomer($request->user(), $orderNumber);
            $cancelledOrder = $this->orderService->cancelOrder($order, $request->reason, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully and inventory was restored.',
                'data' => new OrderResource($cancelledOrder),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}


<?php

namespace App\Domain\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderDelivery;
use App\Domain\Orders\Requests\StoreOrderDeliveryRequest;
use App\Domain\Orders\Resources\OrderDeliveryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderDeliveryController extends Controller
{
    /**
     * Display a listing of deliveries for an order.
     *
     * @param int $orderId
     * @return JsonResponse
     */
    public function index(int $orderId): JsonResponse
    {
        $user = auth()->user();

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Authorization: only buyer, creator, or admin can view deliveries
        if (!$order->isParty($user->id) && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You are not a party in this order.',
            ], 403);
        }

        // Get deliveries ordered from newest to oldest
        $deliveries = $order->deliveries()
            ->newestFirst()
            ->get();

        return response()->json([
            'success' => true,
            'data' => OrderDeliveryResource::collection($deliveries),
        ]);
    }

    /**
     * Store a new delivery for an order.
     *
     * @param StoreOrderDeliveryRequest $request
     * @param int $orderId
     * @return JsonResponse
     */
    public function store(StoreOrderDeliveryRequest $request, int $orderId): JsonResponse
    {
        $user = $request->user();

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Authorization: only creator can submit deliveries
        if (!$order->isCreator($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only the creator can submit deliveries.',
            ], 403);
        }

        // Check if order is in a state that allows delivery
        $allowedStatuses = [
            Order::STATUS_ACCEPTED,
            Order::STATUS_IN_PROGRESS,
            Order::STATUS_DELIVERED, // Allow redelivery
        ];

        if (!in_array($order->status, $allowedStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot submit delivery for this order. Order must be accepted or in progress.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Create delivery
            $delivery = OrderDelivery::create([
                'order_id' => $order->id,
                'file_url' => $request->file_url,
                'message' => $request->message,
            ]);

            // Update order status to delivered
            $order->update([
                'status' => Order::STATUS_DELIVERED,
                'delivery_date' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery submitted successfully. Order status updated to delivered.',
                'data' => new OrderDeliveryResource($delivery),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit delivery.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

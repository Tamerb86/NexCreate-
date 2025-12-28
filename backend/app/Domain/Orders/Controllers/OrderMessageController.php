<?php

namespace App\Domain\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderMessage;
use App\Domain\Orders\Requests\StoreOrderMessageRequest;
use App\Domain\Orders\Resources\OrderMessageResource;
use Illuminate\Http\JsonResponse;

class OrderMessageController extends Controller
{
    /**
     * Display a listing of messages for an order.
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

        // Authorization: only buyer, creator, or admin can view messages
        if (!$order->isParty($user->id) && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You are not a party in this order.',
            ], 403);
        }

        // Get messages ordered from oldest to newest
        $messages = $order->messages()
            ->with('sender')
            ->oldestFirst()
            ->get();

        return response()->json([
            'success' => true,
            'data' => OrderMessageResource::collection($messages),
        ]);
    }

    /**
     * Store a new message for an order.
     *
     * @param StoreOrderMessageRequest $request
     * @param int $orderId
     * @return JsonResponse
     */
    public function store(StoreOrderMessageRequest $request, int $orderId): JsonResponse
    {
        $user = $request->user();

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Authorization: only buyer or creator can send messages
        if (!$order->isParty($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You are not a party in this order.',
            ], 403);
        }

        // Check if order is active (not completed, cancelled, or rejected)
        if (!$order->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send messages to a closed order.',
            ], 400);
        }

        // Create message
        $message = OrderMessage::create([
            'order_id' => $order->id,
            'sender_id' => $user->id,
            'message' => $request->message,
            'attachment' => $request->attachment,
        ]);

        // Load sender relationship
        $message->load('sender');

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => new OrderMessageResource($message),
        ], 201);
    }
}

<?php

namespace App\Domain\Orders\Controllers;

use App\Http\Controllers\Controller;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Requests\UpdateOrderStatusRequest;
use Illuminate\Http\JsonResponse;

class OrderStatusController extends Controller
{
    /**
     * Status transition rules.
     * Format: 'current_status' => ['allowed_new_status' => 'who_can_do_it']
     */
    private array $transitions = [
        Order::STATUS_PENDING => [
            Order::STATUS_ACCEPTED => 'creator',
            Order::STATUS_REJECTED => 'creator',
            Order::STATUS_CANCELLED => 'buyer',
        ],
        Order::STATUS_ACCEPTED => [
            Order::STATUS_IN_PROGRESS => 'creator',
        ],
        Order::STATUS_IN_PROGRESS => [
            Order::STATUS_DELIVERED => 'creator',
        ],
        Order::STATUS_DELIVERED => [
            Order::STATUS_COMPLETED => 'buyer',
        ],
    ];

    /**
     * Update the order status.
     *
     * @param UpdateOrderStatusRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $newStatus = $request->status;

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Authorization: only buyer or creator can update status
        if (!$order->isParty($user->id) && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You are not a party in this order.',
            ], 403);
        }

        // Check if transition is allowed
        $canUpdate = $this->canUpdateStatus($order, $newStatus, $user->id);

        if (!$canUpdate['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $canUpdate['message'],
            ], 422);
        }

        // Update status
        $oldStatus = $order->status;
        $order->update(['status' => $newStatus]);

        // If delivered, set delivery date
        if ($newStatus === Order::STATUS_DELIVERED) {
            $order->update(['delivery_date' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => [
                'id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $order->status,
            ],
        ]);
    }

    /**
     * Check if status update is allowed.
     *
     * @param Order $order
     * @param string $newStatus
     * @param int $userId
     * @return array
     */
    private function canUpdateStatus(Order $order, string $newStatus, int $userId): array
    {
        $currentStatus = $order->status;

        // Check if current status has any allowed transitions
        if (!isset($this->transitions[$currentStatus])) {
            return [
                'allowed' => false,
                'message' => "Cannot change status from '{$currentStatus}'. This order is in a final state.",
            ];
        }

        // Check if the new status is allowed from current status
        if (!isset($this->transitions[$currentStatus][$newStatus])) {
            $allowedStatuses = array_keys($this->transitions[$currentStatus]);
            return [
                'allowed' => false,
                'message' => "Cannot change status from '{$currentStatus}' to '{$newStatus}'. Allowed transitions: " . implode(', ', $allowedStatuses),
            ];
        }

        // Check who can perform this transition
        $requiredRole = $this->transitions[$currentStatus][$newStatus];

        if ($requiredRole === 'buyer' && !$order->isBuyer($userId)) {
            return [
                'allowed' => false,
                'message' => "Only the buyer can change status to '{$newStatus}'.",
            ];
        }

        if ($requiredRole === 'creator' && !$order->isCreator($userId)) {
            return [
                'allowed' => false,
                'message' => "Only the creator can change status to '{$newStatus}'.",
            ];
        }

        return [
            'allowed' => true,
            'message' => 'OK',
        ];
    }
}

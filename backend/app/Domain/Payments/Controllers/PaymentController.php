<?php

namespace App\Domain\Payments\Controllers;

use App\Http\Controllers\Controller;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Requests\CheckoutRequest;
use App\Domain\Payments\Resources\PaymentResource;
use App\Domain\Payments\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get user's payments (as buyer).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $payments = Payment::byBuyer($user->id)
            ->with(['order', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    /**
     * Create checkout session for an order.
     *
     * @param CheckoutRequest $request
     * @return JsonResponse
     */
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $user = $request->user();
        $order = Order::find($request->order_id);

        // Authorization: only buyer can pay
        if (!$order->isBuyer($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only the buyer can pay for this order.',
            ], 403);
        }

        // Check order status
        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'This order cannot be paid. Current status: ' . $order->status,
            ], 400);
        }

        // Check if already paid
        $existingPayment = Payment::where('order_id', $order->id)
            ->where('status', Payment::STATUS_PAID)
            ->first();

        if ($existingPayment) {
            return response()->json([
                'success' => false,
                'message' => 'This order has already been paid.',
            ], 400);
        }

        // Create checkout session
        $result = $this->paymentService->createCheckoutSession($order);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create checkout session.',
                'error' => $result['error'] ?? 'Unknown error',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Checkout session created.',
            'data' => [
                'checkout_url' => $result['checkout_url'],
                'session_id' => $result['session_id'],
            ],
        ]);
    }

    /**
     * Get payment details.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $user = auth()->user();
        $payment = Payment::with(['order', 'buyer', 'creator'])->find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found.',
            ], 404);
        }

        // Authorization: only buyer, creator, or admin
        if ($payment->buyer_id !== $user->id && 
            $payment->creator_id !== $user->id && 
            !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment),
        ]);
    }
}

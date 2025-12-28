<?php

namespace App\Domain\Payments\Controllers;

use App\Http\Controllers\Controller;
use App\Domain\Payments\Models\Payout;
use App\Domain\Payments\Requests\PayoutRequest;
use App\Domain\Payments\Resources\PayoutResource;
use App\Domain\Payments\Resources\BalanceResource;
use App\Domain\Payments\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get creator's balance.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();

        // Authorization: only creators
        if (!$user->isCreator()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only creators can view balance.',
            ], 403);
        }

        $balance = $this->paymentService->getCreatorBalance($user);

        return response()->json([
            'success' => true,
            'data' => new BalanceResource($balance),
        ]);
    }

    /**
     * Get creator's payouts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Authorization: only creators
        if (!$user->isCreator()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only creators can view payouts.',
            ], 403);
        }

        $query = Payout::byCreator($user->id)
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->withStatus($request->status);
        }

        $payouts = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => PayoutResource::collection($payouts),
            'meta' => [
                'current_page' => $payouts->currentPage(),
                'last_page' => $payouts->lastPage(),
                'per_page' => $payouts->perPage(),
                'total' => $payouts->total(),
            ],
        ]);
    }

    /**
     * Request a payout.
     *
     * @param PayoutRequest $request
     * @return JsonResponse
     */
    public function store(PayoutRequest $request): JsonResponse
    {
        $user = $request->user();

        // Authorization already checked in PayoutRequest

        // Check if creator has completed Stripe onboarding
        if (!$user->canReceivePayouts()) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete Stripe onboarding before requesting payouts.',
                'needs_onboarding' => true,
            ], 400);
        }

        $result = $this->paymentService->requestPayout($user, $request->amount);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
                'available' => $result['available'] ?? null,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => new PayoutResource($result['payout']),
        ], 201);
    }

    /**
     * Cancel a payout request.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function cancel(int $id): JsonResponse
    {
        $user = auth()->user();
        $payout = Payout::find($id);

        if (!$payout) {
            return response()->json([
                'success' => false,
                'message' => 'Payout not found.',
            ], 404);
        }

        // Authorization: only the creator who requested it
        if ($payout->creator_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        // Check if can be cancelled
        if (!$payout->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'This payout cannot be cancelled. Current status: ' . $payout->status,
            ], 400);
        }

        $payout->update(['status' => Payout::STATUS_CANCELLED]);

        return response()->json([
            'success' => true,
            'message' => 'Payout cancelled successfully.',
            'data' => new PayoutResource($payout),
        ]);
    }
}

<?php

namespace App\Domain\Payments\Controllers;

use App\Http\Controllers\Controller;
use App\Domain\Payments\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeConnectController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Start or continue Stripe Connect onboarding.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function onboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Authorization: only creators
        if (!$user->isCreator()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only creators can connect Stripe.',
            ], 403);
        }

        // Check if already completed
        if ($user->hasCompletedStripeOnboarding()) {
            return response()->json([
                'success' => true,
                'message' => 'Stripe onboarding already completed.',
                'completed' => true,
            ]);
        }

        // Create account or get onboarding link
        $result = $this->paymentService->createStripeConnectAccount($user);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Stripe Connect account.',
                'error' => $result['error'] ?? 'Unknown error',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Stripe Connect onboarding link generated.',
            'data' => [
                'url' => $result['url'],
            ],
        ]);
    }

    /**
     * Get Stripe Connect status.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        // Authorization: only creators
        if (!$user->isCreator()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only creators can check Stripe status.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'has_account' => $user->hasStripeAccount(),
                'onboarding_complete' => $user->hasCompletedStripeOnboarding(),
                'can_receive_payouts' => $user->canReceivePayouts(),
                'stripe_account_id' => $user->hasStripeAccount() ? substr($user->stripe_account_id, 0, 10) . '...' : null,
            ],
        ]);
    }

    /**
     * Handle Stripe Connect return (after onboarding).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function complete(Request $request): JsonResponse
    {
        $user = $request->user();

        // Authorization: only creators
        if (!$user->isCreator()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only creators can complete Stripe onboarding.',
            ], 403);
        }

        if (!$user->hasStripeAccount()) {
            return response()->json([
                'success' => false,
                'message' => 'No Stripe account found.',
            ], 400);
        }

        // Verify actual account status with Stripe — the redirect back from
        // Stripe does not guarantee onboarding finished.
        $result = $this->paymentService->syncStripeOnboardingStatus($user);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify Stripe onboarding status.',
            ], 502);
        }

        if (!$result['onboarding_complete']) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe onboarding is not complete yet. Please finish the onboarding steps at Stripe.',
                'data' => [
                    'can_receive_payouts' => false,
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Stripe Connect onboarding completed.',
            'data' => [
                'can_receive_payouts' => $user->fresh()->canReceivePayouts(),
            ],
        ]);
    }
}

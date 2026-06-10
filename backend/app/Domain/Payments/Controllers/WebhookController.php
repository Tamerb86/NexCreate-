<?php

namespace App\Domain\Payments\Controllers;

use App\Http\Controllers\Controller;
use App\Domain\Payments\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Exception;

class WebhookController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle Stripe webhook.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('stripe.webhook_secret');

        // Signature verification is mandatory: without it anyone can forge
        // payment events and mark unpaid orders as paid.
        if (!$webhookSecret) {
            report(new Exception('Stripe webhook rejected: STRIPE_WEBHOOK_SECRET is not configured.'));

            return response()->json([
                'success' => false,
                'error' => 'Webhook not configured.',
            ], 503);
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );

            // Handle the event
            $result = $this->paymentService->handleWebhookEvent($event);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'] ?? 'Webhook handled successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to handle webhook',
            ], 400);

        } catch (SignatureVerificationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid signature',
            ], 400);

        } catch (Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'Webhook processing failed.',
            ], 500);
        }
    }
}

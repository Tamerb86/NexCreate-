<?php

namespace App\Domain\Payments\Services;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Payout;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Account as StripeAccount;
use Stripe\AccountLink;
use Stripe\Transfer;
use Stripe\Stripe;
use Exception;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret'));
    }

    /**
     * Create a Stripe Checkout Session for an order.
     *
     * @param Order $order
     * @return array
     * @throws Exception
     */
    public function createCheckoutSession(Order $order): array
    {
        try {
            // Calculate amounts
            $amount = $order->price;
            $commission = $this->calculateCommission($amount);
            $creatorEarning = $amount - $commission;

            // Create pending payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'buyer_id' => $order->buyer_id,
                'creator_id' => $order->creator_id,
                'amount' => $amount,
                'commission' => $commission,
                'creator_earning' => $creatorEarning,
                'currency' => config('stripe.currency', 'nok'),
                'status' => Payment::STATUS_PENDING,
            ]);

            // Create Stripe Checkout Session
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => config('stripe.currency', 'nok'),
                        'product_data' => [
                            'name' => $order->service->title,
                            'description' => "Order #{$order->id} - {$order->service->title}",
                        ],
                        'unit_amount' => (int) ($amount * 100), // Convert to øre
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => config('app.url') . config('stripe.success_url') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.url') . config('stripe.cancel_url'),
                'metadata' => [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'buyer_id' => $order->buyer_id,
                    'creator_id' => $order->creator_id,
                ],
                'client_reference_id' => (string) $order->id,
            ]);

            // Update payment with session ID
            $payment->update([
                'stripe_checkout_session_id' => $session->id,
            ]);

            return [
                'success' => true,
                'checkout_url' => $session->url,
                'session_id' => $session->id,
                'payment_id' => $payment->id,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle Stripe webhook events.
     *
     * @param object $event
     * @return array
     */
    public function handleWebhookEvent(object $event): array
    {
        switch ($event->type) {
            case 'checkout.session.completed':
                return $this->handleCheckoutCompleted($event->data->object);

            case 'charge.refunded':
                return $this->handleChargeRefunded($event->data->object);

            default:
                return ['success' => true, 'message' => 'Event ignored'];
        }
    }

    /**
     * Handle successful checkout.
     *
     * @param object $session
     * @return array
     */
    protected function handleCheckoutCompleted(object $session): array
    {
        try {
            DB::beginTransaction();

            // Find payment by session ID
            $payment = Payment::where('stripe_checkout_session_id', $session->id)->first();

            if (!$payment) {
                throw new Exception('Payment not found for session: ' . $session->id);
            }

            // Calculate available date (after payout delay)
            $availableAt = now()->addDays(config('nexcreate.payout_delay_days', 7));

            // Update payment
            $payment->update([
                'stripe_payment_id' => $session->payment_intent,
                'status' => Payment::STATUS_PAID,
                'paid_at' => now(),
                'available_at' => $availableAt,
            ]);

            // Update order status to accepted
            $order = $payment->order;
            if ($order->status === Order::STATUS_PENDING) {
                $order->update(['status' => Order::STATUS_ACCEPTED]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'payment_id' => $payment->id,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle refund.
     *
     * @param object $charge
     * @return array
     */
    protected function handleChargeRefunded(object $charge): array
    {
        try {
            $payment = Payment::where('stripe_payment_id', $charge->payment_intent)->first();

            if ($payment) {
                $payment->update(['status' => Payment::STATUS_REFUNDED]);
            }

            return [
                'success' => true,
                'message' => 'Refund processed',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate platform commission.
     *
     * @param float $amount
     * @return float
     */
    public function calculateCommission(float $amount): float
    {
        $commissionPercent = config('nexcreate.commission_percent', 0.15);
        return round($amount * $commissionPercent, 2);
    }

    /**
     * Get creator's balance.
     *
     * @param User $creator
     * @return array
     */
    public function getCreatorBalance(User $creator): array
    {
        // Total earned (all paid payments)
        $totalEarned = Payment::byCreator($creator->id)
            ->paid()
            ->sum('creator_earning');

        // Available (paid and past available_at date)
        $availableEarnings = Payment::byCreator($creator->id)
            ->availableForPayout()
            ->sum('creator_earning');

        // Pending (paid but not yet available)
        $pendingEarnings = Payment::byCreator($creator->id)
            ->paid()
            ->where(function ($query) {
                $query->whereNull('available_at')
                    ->orWhere('available_at', '>', now());
            })
            ->sum('creator_earning');

        // Total withdrawn (successful payouts)
        $totalWithdrawn = Payout::byCreator($creator->id)
            ->successful()
            ->sum('amount');

        // Pending payouts (requested but not sent)
        $pendingPayouts = Payout::byCreator($creator->id)
            ->whereIn('status', [Payout::STATUS_PENDING, Payout::STATUS_APPROVED, Payout::STATUS_PROCESSING])
            ->sum('amount');

        // Available balance = available earnings - withdrawn - pending payouts
        $available = $availableEarnings - $totalWithdrawn - $pendingPayouts;

        return [
            'available' => max(0, $available),
            'pending' => (float) $pendingEarnings,
            'total_earned' => (float) $totalEarned,
            'total_withdrawn' => (float) $totalWithdrawn,
            'pending_payouts' => (float) $pendingPayouts,
        ];
    }

    /**
     * Request a payout.
     *
     * @param User $creator
     * @param float $amount
     * @return array
     */
    public function requestPayout(User $creator, float $amount): array
    {
        try {
            // Get balance
            $balance = $this->getCreatorBalance($creator);

            // Check minimum payout
            $minimumPayout = config('nexcreate.minimum_payout', 100);
            if ($amount < $minimumPayout) {
                return [
                    'success' => false,
                    'error' => "Minimum payout amount is {$minimumPayout} NOK",
                ];
            }

            // Check available balance
            if ($amount > $balance['available']) {
                return [
                    'success' => false,
                    'error' => 'Insufficient available balance',
                    'available' => $balance['available'],
                ];
            }

            // Create payout request
            $payout = Payout::create([
                'creator_id' => $creator->id,
                'amount' => $amount,
                'status' => Payout::STATUS_PENDING,
            ]);

            return [
                'success' => true,
                'message' => 'Payout request submitted successfully',
                'payout' => $payout,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create Stripe Connect account for creator.
     *
     * @param User $creator
     * @return array
     */
    public function createStripeConnectAccount(User $creator): array
    {
        try {
            // Check if already has account
            if ($creator->hasStripeAccount()) {
                return $this->getOnboardingLink($creator);
            }

            // Create Express account
            $account = StripeAccount::create([
                'type' => 'express',
                'country' => config('stripe.connect.country', 'NO'),
                'email' => $creator->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_type' => 'individual',
                'metadata' => [
                    'user_id' => $creator->id,
                    'platform' => config('nexcreate.name'),
                ],
            ]);

            // Save account ID
            $creator->update([
                'stripe_account_id' => $account->id,
            ]);

            // Get onboarding link
            return $this->getOnboardingLink($creator);

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get Stripe Connect onboarding link.
     *
     * @param User $creator
     * @return array
     */
    public function getOnboardingLink(User $creator): array
    {
        try {
            if (!$creator->hasStripeAccount()) {
                return [
                    'success' => false,
                    'error' => 'No Stripe account found',
                ];
            }

            $accountLink = AccountLink::create([
                'account' => $creator->stripe_account_id,
                'refresh_url' => config('app.url') . '/stripe/connect/refresh',
                'return_url' => config('app.url') . '/stripe/connect/complete',
                'type' => 'account_onboarding',
            ]);

            return [
                'success' => true,
                'url' => $accountLink->url,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process a payout via Stripe Transfer.
     *
     * @param Payout $payout
     * @return array
     */
    public function processPayout(Payout $payout): array
    {
        try {
            $creator = $payout->creator;

            if (!$creator->canReceivePayouts()) {
                return [
                    'success' => false,
                    'error' => 'Creator has not completed Stripe onboarding',
                ];
            }

            // Create transfer to connected account
            $transfer = Transfer::create([
                'amount' => (int) ($payout->amount * 100), // Convert to øre
                'currency' => config('stripe.currency', 'nok'),
                'destination' => $creator->stripe_account_id,
                'metadata' => [
                    'payout_id' => $payout->id,
                    'creator_id' => $creator->id,
                ],
            ]);

            // Update payout
            $payout->update([
                'stripe_transfer_id' => $transfer->id,
                'status' => Payout::STATUS_SENT,
                'processed_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Payout processed successfully',
                'transfer_id' => $transfer->id,
            ];

        } catch (Exception $e) {
            $payout->update([
                'status' => Payout::STATUS_FAILED,
                'failure_reason' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

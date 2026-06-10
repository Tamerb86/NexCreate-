<?php

namespace Tests\Feature;

use Tests\TestCase;

class StripeWebhookSecurityTest extends TestCase
{
    /**
     * Without a configured webhook secret the endpoint must refuse to
     * process anything — accepting unsigned payloads lets anyone mark
     * unpaid orders as paid.
     */
    public function test_webhook_is_rejected_when_secret_is_not_configured(): void
    {
        config(['stripe.webhook_secret' => null]);

        $this->postJson('/api/v1/payments/webhook', [
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_forged']],
        ])->assertStatus(503);
    }

    /**
     * With a secret configured, a payload that fails signature
     * verification must be rejected with 400.
     */
    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        config(['stripe.webhook_secret' => 'whsec_test_secret']);

        $this->postJson('/api/v1/payments/webhook', [
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_forged']],
        ], [
            'Stripe-Signature' => 't=1,v1=invalid',
        ])->assertStatus(400);
    }

    /**
     * A missing signature header must also be rejected, not crash.
     */
    public function test_webhook_without_signature_header_is_rejected(): void
    {
        config(['stripe.webhook_secret' => 'whsec_test_secret']);

        $this->postJson('/api/v1/payments/webhook', [
            'type' => 'checkout.session.completed',
        ])->assertStatus(400);
    }
}

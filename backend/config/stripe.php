<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe API Keys
    |--------------------------------------------------------------------------
    |
    | Your Stripe API keys. You can find these in your Stripe dashboard.
    |
    */

    'secret' => env('STRIPE_SECRET'),
    'public' => env('STRIPE_PUBLIC'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Webhook Secret
    |--------------------------------------------------------------------------
    |
    | The webhook secret is used to verify that webhook requests are coming
    | from Stripe. You can find this in your Stripe dashboard under Webhooks.
    |
    */

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Connect
    |--------------------------------------------------------------------------
    |
    | Settings for Stripe Connect (marketplace payments).
    |
    */

    'connect' => [
        'client_id' => env('STRIPE_CONNECT_CLIENT_ID'),
        'account_type' => 'express', // express, standard, or custom
        'country' => 'NO', // Norway
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Default currency for payments.
    |
    */

    'currency' => env('STRIPE_CURRENCY', 'nok'),

    /*
    |--------------------------------------------------------------------------
    | Success and Cancel URLs
    |--------------------------------------------------------------------------
    |
    | URLs to redirect to after checkout.
    |
    */

    'success_url' => env('STRIPE_SUCCESS_URL', '/payment/success'),
    'cancel_url' => env('STRIPE_CANCEL_URL', '/payment/cancel'),

];

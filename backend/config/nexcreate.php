<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Commission
    |--------------------------------------------------------------------------
    |
    | The percentage of each transaction that goes to the platform.
    | Example: 0.15 = 15%
    |
    */

    'commission_percent' => env('NEXCREATE_COMMISSION_PERCENT', 0.15),

    /*
    |--------------------------------------------------------------------------
    | Minimum Payout Amount
    |--------------------------------------------------------------------------
    |
    | The minimum amount (in NOK) that a creator must have to request a payout.
    |
    */

    'minimum_payout' => env('NEXCREATE_MINIMUM_PAYOUT', 100),

    /*
    |--------------------------------------------------------------------------
    | Payout Processing Days
    |--------------------------------------------------------------------------
    |
    | Number of days before a payment becomes available for payout.
    | This is a safety buffer for refunds.
    |
    */

    'payout_delay_days' => env('NEXCREATE_PAYOUT_DELAY_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Platform Name
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'NexCreate'),

    /*
    |--------------------------------------------------------------------------
    | Platform URL
    |--------------------------------------------------------------------------
    */

    'url' => env('APP_URL', 'http://localhost'),

];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Banking details shown to signed-in clients
    |--------------------------------------------------------------------------
    | These values appear on the Bank Details page and on deposit instructions.
    | Override them in .env without touching code.
    */

    'bank_name' => env('BANK_NAME', 'ConchCapital Bank (Cayman) Ltd.'),

    'bank_address' => env('BANK_ADDRESS', 'Harbour Centre, 2nd Floor, Grand Cayman KY1-1108, Cayman Islands'),

    'swift' => env('BANK_SWIFT', 'CCBKKYKY'),

    'routing' => env('BANK_ROUTING', '021000089'),

    'account_holder' => env('BANK_ACCOUNT_HOLDER', 'ConchCapital Client Settlement Account'),

    'deposit_reference_prefix' => env('BANK_DEPOSIT_PREFIX', 'DEP'),

    /*
    |--------------------------------------------------------------------------
    | Withdrawal fee
    |--------------------------------------------------------------------------
    | Percentage deducted from the withdrawn amount (see fee-schedule.html).
    */

    'withdrawal_fee_percent' => (float) env('BANK_WITHDRAWAL_FEE_PERCENT', 1.5),

    'support_email' => env('BANK_SUPPORT_EMAIL', 'support@conchcapital.co'),

    'support_phone' => env('BANK_SUPPORT_PHONE', '+1 (345) 949-8800'),

    /*
    |--------------------------------------------------------------------------
    | Relationship manager shown on the dashboard
    |--------------------------------------------------------------------------
    */
    'rm_name' => env('BANK_RM_NAME', 'Alexandra Reid'),
    'rm_title' => env('BANK_RM_TITLE', 'Senior Client Advisor'),
    'rm_email' => env('BANK_RM_EMAIL', 'advisory@conchcapital.co'),
    'rm_phone' => env('BANK_RM_PHONE', '+1 (345) 949-8812'),
    'rm_booking_url' => env('BANK_RM_BOOKING_URL', ''),
];

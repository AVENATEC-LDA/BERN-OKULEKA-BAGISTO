<?php

return [
    'admin' => [
        'system' => [
            'name'                => 'Unitel Money',
            'info'                => 'Unitel Money payment gateway for Angola.',
            'active'              => 'Status',
            'title'               => 'Title',
            'description'         => 'Description',
            'sandbox'             => 'Sandbox Mode',
            'client-id'           => 'Client ID',
            'client-secret'       => 'Client Secret',
            'merchant-id'         => 'Merchant ID',
            'service-code'        => 'Service Code',
            'sandbox-base-url'    => 'Sandbox Base URL',
            'production-base-url' => 'Production Base URL',
            'oauth-path'          => 'OAuth Path',
            'buy-goods-path'      => 'BuyGoods Async Path',
            'query-status-path'   => 'Query Status Path',
            'callback-secret'     => 'Callback Secret',
            'allowed-ips'         => 'Allowed Callback IPs',
            'sort'                => 'Sort Order',
        ],
    ],

    'shop' => [
        'waiting' => [
            'title'        => 'Confirm your Unitel Money payment',
            'message'      => 'A payment confirmation was sent to your Unitel Money phone. Approve it to complete your order.',
            'order'        => 'Order #:order is waiting for payment confirmation.',
            'back-to-cart' => 'Back to cart',
        ],
    ],

    'messages' => [
        'invalid-cart'      => 'Unable to start Unitel Money payment for this cart.',
        'initiation-failed' => 'Unable to start Unitel Money payment. Please try again.',
        'missing-reference' => 'Unitel Money payment reference was not found.',
        'query-complete'    => 'Unitel Money status query completed.',
        'query-failed'      => 'Unable to query Unitel Money payment status.',
    ],

    'reasons' => [
        'payment_success'                      => 'Payment approved',
        'customer_cancelled'                   => 'Customer cancelled confirmation',
        'insufficient_funds'                   => 'Insufficient funds',
        'incorrect_pin_or_security_credential' => 'Incorrect PIN or security credential',
        'invalid_or_inactive_wallet'           => 'Invalid or inactive wallet',
        'msisdn_verification_failed'           => 'MSISDN verification failed',
        'payment_failed'                       => 'Payment failed',
    ],

    'customer-reasons' => [
        'payment_success'                      => 'Your Unitel Money payment was approved.',
        'customer_cancelled'                   => 'You cancelled or did not approve the Unitel Money confirmation.',
        'insufficient_funds'                   => 'Your Unitel Money wallet has insufficient funds.',
        'incorrect_pin_or_security_credential' => 'The PIN or security credential was incorrect.',
        'invalid_or_inactive_wallet'           => 'The Unitel Money wallet is invalid or inactive.',
        'msisdn_verification_failed'           => 'The phone number could not be validated by Unitel Money.',
        'payment_failed'                       => 'The Unitel Money payment failed.',
    ],

    'admin-reasons' => [
        'payment_success'                      => 'Unitel Money approved the payment.',
        'customer_cancelled'                   => 'Unitel Money returned 401901: customer cancelled or abandoned confirmation.',
        'insufficient_funds'                   => 'Unitel Money returned 3007001: insufficient funds.',
        'incorrect_pin_or_security_credential' => 'Unitel Money returned 3013001: incorrect PIN or security credential.',
        'invalid_or_inactive_wallet'           => 'Unitel Money returned 3013002: invalid or inactive wallet.',
        'msisdn_verification_failed'           => 'Unitel Money returned 3015: MSISDN verification failed.',
        'payment_failed'                       => 'Unitel Money returned an unmapped failure.',
    ],
];

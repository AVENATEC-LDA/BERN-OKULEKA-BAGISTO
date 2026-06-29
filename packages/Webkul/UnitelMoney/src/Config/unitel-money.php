<?php

return [
    'active'              => env('UNITEL_MONEY_ACTIVE', false),
    'sandbox'             => env('UNITEL_MONEY_SANDBOX', true),
    'client_id'           => env('UNITEL_MONEY_CLIENT_ID'),
    'client_secret'       => env('UNITEL_MONEY_CLIENT_SECRET'),
    'merchant_id'         => env('UNITEL_MONEY_MERCHANT_ID'),
    'service_code'        => env('UNITEL_MONEY_SERVICE_CODE'),
    'initiator_name'      => env('UNITEL_MONEY_INITIATOR_NAME'),
    'initiator_password'  => env('UNITEL_MONEY_INITIATOR_PASSWORD'),
    'party_id'            => env('UNITEL_MONEY_PARTY_ID'),
    'party_type'          => env('UNITEL_MONEY_PARTY_TYPE'),
    'sandbox_base_url'    => env('UNITEL_MONEY_SANDBOX_BASE_URL'),
    'production_base_url' => env('UNITEL_MONEY_PRODUCTION_BASE_URL'),
    'oauth_path'          => env('UNITEL_MONEY_OAUTH_PATH', '/oauth2/token'),
    'buy_goods_path'      => env('UNITEL_MONEY_BUY_GOODS_PATH', '/buyGoods_async'),
    'query_status_path'   => env('UNITEL_MONEY_QUERY_STATUS_PATH', '/queryTransactionStatus'),
    'callback_secret'     => env('UNITEL_MONEY_CALLBACK_SECRET'),
    'allowed_ips'         => env('UNITEL_MONEY_ALLOWED_IPS'),
];

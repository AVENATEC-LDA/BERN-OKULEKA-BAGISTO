<?php

use Webkul\UnitelMoney\Payment\UnitelMoney;

return [
    'unitel_money' => [
        'class'       => UnitelMoney::class,
        'code'        => 'unitel_money',
        'title'       => 'Unitel Money',
        'description' => 'Pay securely with Unitel Money.',
        'active'      => false,
        'sandbox'     => true,
        'sort'        => 3,
    ],
];

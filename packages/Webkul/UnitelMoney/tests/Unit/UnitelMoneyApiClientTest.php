<?php

use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartPayment;
use Webkul\UnitelMoney\Services\UnitelMoneyApiClient;
use Webkul\UnitelMoney\Services\UnitelMoneyPayloadMasker;

it('uses the unitel phone from cart payment additional data when available', function () {
    $cart = new Cart;
    $cart->setRelation('payment', new CartPayment([
        'additional' => [
            'unitel_money_phone' => '912345678',
        ],
    ]));

    $apiClient = new UnitelMoneyApiClient(new UnitelMoneyPayloadMasker());

    expect($apiClient->resolvePhone($cart))->toBe('912345678');
});

it('validates the normalized phone length', function () {
    $apiClient = new UnitelMoneyApiClient(new UnitelMoneyPayloadMasker());

    expect($apiClient->isPhoneValid('912345678'))
        ->toBeTrue()
        ->and($apiClient->isPhoneValid('12345'))->toBeFalse();
});

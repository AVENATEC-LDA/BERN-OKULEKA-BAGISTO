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

it('builds a nested buyGoods_async payload with transaction and identity sections', function () {
    config()->set('unitel_money.merchant_id', 'merchant-1');
    config()->set('unitel_money.service_code', 'service-1');
    config()->set('unitel_money.initiator_name', 'initiator');
    config()->set('unitel_money.initiator_password', 'secret');
    config()->set('unitel_money.party_id', 'party-1');
    config()->set('unitel_money.party_type', 'merchant');

    $cart = new Cart;
    $cart->id = 42;
    $cart->setRelation('payment', new CartPayment([
        'additional' => [
            'unitel_money_phone' => '912345678',
        ],
    ]));

    $order = new \Webkul\Sales\Models\Order;
    $order->id = 7;
    $order->grand_total = 123.45;
    $order->order_currency_code = 'AOA';

    $apiClient = new UnitelMoneyApiClient(new UnitelMoneyPayloadMasker());
    $method = new ReflectionMethod(UnitelMoneyApiClient::class, 'buildBuyGoodsPayload');
    $method->setAccessible(true);
    $payload = $method->invoke($apiClient, $cart, $order);

    expect($payload)
        ->toHaveKey('BuyGoodRec')
        ->toHaveKey('IdentityRec')
        ->and($payload['BuyGoodRec']['TransactionRequest']['OriginatorConversationID'])->toStartWith('BAGISTO-7-')
        ->and($payload['BuyGoodRec']['TransactionRequest']['MSISDN'])->toBe('912345678')
        ->and($payload['BuyGoodRec']['TransactionRequest']['ReferenceData']['ReferenceItem']['Key'])->toBe('cart_id')
        ->and($payload['IdentityRec']['PartyID'])->toBe('party-1');
});

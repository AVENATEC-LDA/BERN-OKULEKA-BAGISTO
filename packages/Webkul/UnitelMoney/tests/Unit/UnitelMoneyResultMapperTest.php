<?php

use Webkul\UnitelMoney\Services\UnitelMoneyResultMapper;

it('maps successful buy goods result', function () {
    $context = app(UnitelMoneyResultMapper::class)->map([
        'ResultBuyGoods' => [
            'ResultCode' => 0,
            'ResultDesc' => 'Success',
        ],
    ]);

    expect($context['status_group'])->toBe('success')
        ->and($context['reason_key'])->toBe('payment_success')
        ->and($context['template_key'])->toBe('unitel_money_payment_success')
        ->and($context['order_status'])->toBe('processing')
        ->and($context['invoice_state'])->toBe('paid');
});

it('maps homologated Unitel Money failure codes', function (string $code, string $reason, string $template) {
    $context = app(UnitelMoneyResultMapper::class)->map([
        'Result' => [
            'ResultCode' => $code,
            'ResultDesc' => 'Failure',
        ],
    ]);

    expect($context['status_group'])->toBe('failed')
        ->and($context['reason_key'])->toBe($reason)
        ->and($context['template_key'])->toBe($template)
        ->and($context['order_status'])->toBe('canceled')
        ->and($context['invoice_state'])->toBeNull();
})->with([
    'customer cancelled' => ['401901', 'customer_cancelled', 'unitel_money_failed_customer_cancelled'],
    'insufficient funds' => ['3007001', 'insufficient_funds', 'unitel_money_failed_insufficient_funds'],
    'incorrect pin'     => ['3013001', 'incorrect_pin_or_security_credential', 'unitel_money_failed_incorrect_pin'],
    'invalid wallet'    => ['3013002', 'invalid_or_inactive_wallet', 'unitel_money_failed_invalid_wallet'],
    'msisdn failed'     => ['3015', 'msisdn_verification_failed', 'unitel_money_failed_msisdn'],
]);

it('maps empty result codes as pending', function () {
    $context = app(UnitelMoneyResultMapper::class)->map([
        'Result' => [
            'ResultCode' => '',
            'ResultDesc' => 'Awaiting confirmation',
        ],
    ]);

    expect($context['status_group'])->toBe('pending')
        ->and($context['reason_key'])->toBe('payment_failed')
        ->and($context['template_key'])->toBe('unitel_money_failed_generic')
        ->and($context['order_status'])->toBe('pending')
        ->and($context['invoice_state'])->toBeNull();
});

it('extracts identifiers from nested payloads', function () {
    $identifiers = app(UnitelMoneyResultMapper::class)->extractIdentifiers([
        'ResultBuyGoods' => [
            'OriginatorConversationID' => 'orig-1',
            'ConversationID' => 'conv-1',
            'TransactionID' => 'tx-1',
            'ReferenceData' => [
                'ReferenceItem' => [
                    'Key' => 'cart_id',
                    'Value' => '99',
                ],
            ],
        ],
    ]);

    expect($identifiers['originator_conversation_id'])->toBe('orig-1')
        ->and($identifiers['conversation_id'])->toBe('conv-1')
        ->and($identifiers['transaction_id'])->toBe('tx-1')
        ->and($identifiers['cart_id'])->toBe('99');
});

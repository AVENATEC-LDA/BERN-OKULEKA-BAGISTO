<?php

use Avenatec\EmisPayment\Payment\EmisPayment;

it('builds an emis reference with prefix and order id', function () {
    $payment = new EmisPayment;

    expect($payment->buildReference(1234, 'BERNO'))->toBe('BERNO1234');
});

it('limits emis reference to fifteen alphanumeric characters', function () {
    $payment = new EmisPayment;

    expect($payment->buildReference(9876543210, 'BERNO-STORE'))->toBe('BERNOSTORE98765');
});

it('converts amounts to integer aoa', function () {
    $payment = new EmisPayment;

    expect($payment->toAoa(300.49))->toBe(300)
        ->and($payment->toAoa(300.50))->toBe(301);
});

it('maps emis statuses to bagisto order statuses', function (string $emisStatus, ?string $orderStatus) {
    $payment = new EmisPayment;

    expect($payment->resolveOrderStatus($emisStatus))->toBe($orderStatus);
})->with([
    ['ACCEPTED', 'processing'],
    ['SUCCESS', 'processing'],
    ['PAID', 'processing'],
    ['COMPLETED', 'processing'],
    ['REJECTED', 'canceled'],
    ['FAILED', 'canceled'],
    ['CANCELLED', 'canceled'],
    ['EXPIRED', 'canceled'],
    ['PENDING', null],
]);

it('masks frame tokens', function () {
    $payment = new EmisPayment;

    expect($payment->mask('1234567890abcdef'))->toBe('1234********cdef')
        ->and($payment->mask('12345678'))->toBe('********');
});

it('returns the default multicaixa express checkout logo', function () {
    $payment = new class extends EmisPayment
    {
        public function getConfigData($field)
        {
            return null;
        }
    };

    expect($payment->getImage())->toContain('payment-methods/multicaixa-express.png');
});

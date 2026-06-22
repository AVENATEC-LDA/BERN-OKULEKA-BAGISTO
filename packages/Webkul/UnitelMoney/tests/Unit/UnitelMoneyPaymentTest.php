<?php

use Webkul\UnitelMoney\Payment\UnitelMoney;

it('returns the default unitel money checkout logo', function () {
    $payment = new class extends UnitelMoney
    {
        public function getConfigData($field)
        {
            return null;
        }
    };

    expect($payment->getImage())->toContain('payment-methods/unitel-money.png');
});

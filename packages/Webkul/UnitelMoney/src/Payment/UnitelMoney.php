<?php

namespace Webkul\UnitelMoney\Payment;

use Illuminate\Support\Facades\Storage;
use Webkul\Payment\Payment\Payment;

class UnitelMoney extends Payment
{
    protected $code = 'unitel_money';

    public function getRedirectUrl()
    {
        return route('unitel-money.redirect');
    }

    public function getImage()
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : asset('payment-methods/unitel-money.png');
    }

    public function isAvailable()
    {
        return parent::isAvailable()
            && $this->getConfigData('client_id')
            && $this->getConfigData('client_secret')
            && $this->getConfigData('merchant_id')
            && $this->getConfigData('service_code')
            && $this->getConfigData('callback_secret');
    }

    public function getConfigData($field)
    {
        $adminValue = parent::getConfigData($field);

        if ($adminValue !== null && $adminValue !== '') {
            return $adminValue;
        }

        return config('unitel_money.'.$field);
    }
}

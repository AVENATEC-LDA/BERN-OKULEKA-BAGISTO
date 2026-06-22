<?php

namespace Webkul\UnitelMoney\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Webkul\Checkout\Contracts\Cart;
use Webkul\Sales\Contracts\Order;

class UnitelMoneyApiClient
{
    public function __construct(
        protected UnitelMoneyPayloadMasker $masker
    ) {}

    public function initiatePayment(Cart $cart, Order $order): array
    {
        $payload = $this->buildBuyGoodsPayload($cart, $order);

        $response = $this->http()
            ->withToken($this->getAccessToken())
            ->post($this->endpoint('buy_goods_path'), $payload)
            ->throw()
            ->json();

        return [
            'request'  => $payload,
            'response' => $response,
        ];
    }

    public function queryTransactionStatus(string $originatorConversationId): array
    {
        return $this->http()
            ->withToken($this->getAccessToken())
            ->post($this->endpoint('query_status_path'), [
                'OriginatorConversationID' => $originatorConversationId,
                'MerchantID'               => $this->config('merchant_id'),
                'ServiceCode'              => $this->config('service_code'),
            ])
            ->throw()
            ->json();
    }

    public function getAccessToken(): string
    {
        $response = $this->http()
            ->asForm()
            ->post($this->endpoint('oauth_path'), [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->config('client_id'),
                'client_secret' => $this->config('client_secret'),
            ])
            ->throw()
            ->json();

        return $response['access_token'] ?? $response['token'] ?? '';
    }

    public function masked(mixed $payload): mixed
    {
        return $this->masker->mask($payload);
    }

    protected function buildBuyGoodsPayload(Cart $cart, Order $order): array
    {
        return [
            'OriginatorConversationID' => $this->originatorConversationId($order),
            'MerchantID'               => $this->config('merchant_id'),
            'ServiceCode'              => $this->config('service_code'),
            'Amount'                   => (string) round((float) $order->grand_total, 2),
            'Currency'                 => $order->order_currency_code,
            'MSISDN'                   => $this->phone($cart),
            'CallBackURL'              => route('unitel-money.callback', ['token' => $this->config('callback_secret')]),
            'ReferenceData'            => [
                'ReferenceItem' => [
                    'Key'   => 'cart_id',
                    'Value' => (string) $cart->id,
                ],
            ],
        ];
    }

    protected function originatorConversationId(Order $order): string
    {
        return 'BAGISTO-'.$order->id.'-'.now()->format('YmdHis');
    }

    protected function phone(Cart $cart): ?string
    {
        return preg_replace('/\D+/', '', (string) ($cart->billing_address?->phone ?? $cart->shipping_address?->phone));
    }

    protected function http(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->timeout(30);
    }

    protected function endpoint(string $pathConfig): string
    {
        return rtrim($this->baseUrl(), '/').'/'.ltrim((string) $this->config($pathConfig), '/');
    }

    protected function baseUrl(): string
    {
        if ($this->config('sandbox')) {
            return (string) $this->config('sandbox_base_url');
        }

        return (string) $this->config('production_base_url');
    }

    protected function config(string $field): mixed
    {
        $adminValue = core()->getConfigData('sales.payment_methods.unitel_money.'.$field);

        if ($adminValue !== null && $adminValue !== '') {
            return $adminValue;
        }

        return config('unitel_money.'.$field);
    }
}

<?php

use Avenatec\EmisPayment\Payment\EmisPayment;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\OrderTransactionRepository;

use function Avenatec\EmisPayment\Tests\emisFakeOrder;

it('redirects emis payment without active session to cart', function () {
    $response = $this->get(route('emis_payment.redirect'));

    $response->assertRedirect(route('shop.checkout.cart.index'));
});

it('renders emis payment page with iframe when session has frame id', function () {
    $order = emisFakeOrder();
    $order->grand_total = 300.0;
    $order->order_currency_code = 'AOA';

    $this->mock(OrderRepository::class, function ($mock) use ($order) {
        $mock->shouldReceive('find')->with(1234)->andReturn($order);
    });

    $this->mock(InvoiceRepository::class);
    $this->mock(OrderTransactionRepository::class);
    $this->mock(CartRepository::class);

    $payment = new class extends EmisPayment
    {
        public function getConfigData($field)
        {
            return $field === 'frame_host'
                ? 'https://pagamentonline.emis.co.ao/online-payment-gateway/portal/frame?token='
                : null;
        }
    };

    $this->app->instance(EmisPayment::class, $payment);

    $response = $this
        ->withSession([
            'emis_frame_id' => 'frame-token-123',
            'emis_order_id' => 1234,
        ])
        ->get(route('emis_payment.pay'));

    $response->assertSuccessful()
        ->assertSee('https://pagamentonline.emis.co.ao/online-payment-gateway/portal/frame?token=frame-token-123', false);
});

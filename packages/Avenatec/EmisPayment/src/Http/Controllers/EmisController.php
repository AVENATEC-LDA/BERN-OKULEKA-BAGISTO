<?php

namespace Avenatec\EmisPayment\Http\Controllers;

use Avenatec\EmisPayment\Payment\EmisPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\OrderTransactionRepository;
use Webkul\Sales\Transformers\OrderResource;

class EmisController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository,
        protected OrderTransactionRepository $orderTransactionRepository,
        protected CartRepository $cartRepository,
        protected EmisPayment $emisPayment
    ) {}

    public function redirect(Request $request): RedirectResponse
    {
        $cart = Cart::getCart();

        if (! $cart || $cart->payment?->method !== 'emis_payment') {
            Log::channel('single')->error('[EMIS][ETAPA_1] Carrinho invalido para gateway EMIS.');

            session()->flash('error', trans('emis-payment::app.shop.invalid-session'));

            return redirect()->route('shop.checkout.cart.index');
        }

        try {
            Cart::collectTotals();

            $order = $this->findActiveOrderByCartId($cart->id);

            if (! $order) {
                $order = $this->orderRepository->create((new OrderResource($cart))->jsonSerialize());
            }

            Log::channel('single')->info('[EMIS][ETAPA_1] Checkout iniciado.', [
                'order_id' => $order->id,
                'amount'   => $order->grand_total,
                'currency' => $order->order_currency_code,
            ]);

            $webhookUrl = $this->emisPayment->getWebhookUrl();

            Log::channel('single')->info('[EMIS][ETAPA_1] URL publica do webhook preparada.', [
                'order_id'    => $order->id,
                'webhook_url' => $webhookUrl,
            ]);

            $frame = $this->emisPayment->requestFrameToken(
                $order->id,
                (float) $order->grand_total,
                $webhookUrl
            );

            $this->mergePaymentAdditional($order, [
                'emis_reference' => $this->emisPayment->buildReference($order->id),
                'emis_frame_id'  => $this->emisPayment->mask((string) $frame['id']),
                'emis_status'    => 'frame_token_created',
            ]);

            session([
                'emis_frame_id' => $frame['id'],
                'emis_order_id' => $order->id,
            ]);

            Log::channel('single')->info('[EMIS][ETAPA_1] Token obtido. Redirecionando para pagina de pagamento.', [
                'order_id' => $order->id,
                'frame_id' => $this->emisPayment->mask((string) $frame['id']),
            ]);

            return redirect()->route('emis_payment.pay');
        } catch (\Throwable $e) {
            report($e);

            Log::channel('single')->error('[EMIS][ETAPA_1] Erro ao iniciar pagamento.', [
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', trans('emis-payment::app.shop.initiation-failed'));

            return redirect()->route('shop.checkout.cart.index');
        }
    }

    public function pay(): RedirectResponse|View
    {
        $frameId = session('emis_frame_id');
        $orderId = session('emis_order_id');

        if (! $frameId || ! $orderId) {
            Log::channel('single')->warning('[EMIS][ETAPA_3] Sessao de pagamento invalida ou expirada.');

            session()->flash('error', trans('emis-payment::app.shop.expired-session'));

            return redirect()->route('shop.checkout.cart.index');
        }

        $order = $this->findOrder((int) $orderId);

        if (! $order || $order->payment?->method !== 'emis_payment') {
            session()->flash('error', trans('emis-payment::app.shop.order-not-found'));

            return redirect()->route('shop.checkout.cart.index');
        }

        if (in_array($order->status, ['processing', 'completed'], true)) {
            session()->forget(['emis_frame_id', 'emis_order_id']);
            session()->flash('order_id', $order->id);

            return redirect()->route('shop.checkout.onepage.success');
        }

        $frameHost = $this->emisPayment->getConfigData('frame_host') ?: EmisPayment::FRAME_HOST_DEFAULT;
        $iframeSrc = $frameHost.rawurlencode((string) $frameId);
        $successUrl = route('shop.checkout.onepage.success');
        $cancelUrl = route('shop.checkout.cart.index');
        $storeName = config('app.name', 'Loja Online');
        $logoUrl = core()->getCurrentChannel()->logo_url ?? '';
        $orderTotal = number_format((float) $order->grand_total, 2, '.', '').' '.$order->order_currency_code;

        Log::channel('single')->info('[EMIS][ETAPA_3] Pagina de pagamento carregada.', [
            'order_id' => $orderId,
            'frame_id' => $this->emisPayment->mask((string) $frameId),
        ]);

        return view('emis-payment::payment-page', compact(
            'iframeSrc',
            'successUrl',
            'cancelUrl',
            'storeName',
            'logoUrl',
            'orderId',
            'orderTotal'
        ));
    }

    public function webhook(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();

        Log::channel('single')->info('[EMIS][ETAPA_4_WEBHOOK] Callback recebido.', [
            'ip'         => $request->ip(),
            'method'     => $request->method(),
            'body_bruto' => $rawBody,
            'query'      => $request->query(),
        ]);

        $payload = $this->extractWebhookPayload($request, $rawBody);

        if ($payload === []) {
            if ($request->isMethod('get')) {
                Log::channel('single')->info('[EMIS][ETAPA_4_WEBHOOK] Endpoint publico confirmado sem payload.');

                return response()->json([
                    'ok'      => true,
                    'ready'   => true,
                    'message' => 'EMIS webhook endpoint is public and listening.',
                ]);
            }

            Log::channel('single')->error('[EMIS][ETAPA_4_WEBHOOK] Payload invalido ou vazio.');

            return response()->json(['ok' => false, 'error' => 'invalid_payload'], 400);
        }

        $merchantReference = $this->extractMerchantReference($payload);

        if (! preg_match('/(\d+)$/', (string) $merchantReference, $matches)) {
            Log::channel('single')->error('[EMIS][ETAPA_4_WEBHOOK] Referencia invalida.', [
                'merchant_reference' => $merchantReference,
            ]);

            return response()->json(['ok' => false, 'error' => 'invalid_reference'], 400);
        }

        $orderId = (int) $matches[1];
        $order = $this->findOrder($orderId);

        if (! $order) {
            Log::channel('single')->error('[EMIS][ETAPA_4_WEBHOOK] Pedido nao encontrado.', [
                'order_id' => $orderId,
            ]);

            return response()->json(['ok' => false, 'error' => 'order_not_found'], 404);
        }

        if ($order->payment?->method !== 'emis_payment') {
            Log::channel('single')->warning('[EMIS][ETAPA_4_WEBHOOK] Pedido nao pertence ao gateway EMIS.', [
                'order_id' => $orderId,
                'method'   => $order->payment?->method,
            ]);

            return response()->json(['ok' => false, 'error' => 'invalid_payment_method'], 400);
        }

        $emisStatus = $this->extractPaymentStatus($payload);
        $newStatus = $this->emisPayment->resolveOrderStatus($emisStatus);

        $this->mergePaymentAdditional($order, [
            'emis_webhook'        => $payload,
            'emis_transaction_id' => $payload['id'] ?? $payload['transactionId'] ?? $payload['transaction_id'] ?? null,
            'emis_status'         => strtoupper($emisStatus),
        ]);

        if ($newStatus === 'processing') {
            if (in_array($order->status, ['processing', 'completed'], true)) {
                Log::channel('single')->warning('[EMIS][ETAPA_4_WEBHOOK] Pedido ja estava pago. Ignorado.', [
                    'order_id' => $orderId,
                ]);

                return response()->json(['ok' => true, 'note' => 'already_paid']);
            }

            $this->orderRepository->update(['status' => 'processing'], $orderId);
            $invoice = $order->canInvoice()
                ? $this->invoiceRepository->create($this->prepareInvoiceData($order))
                : null;

            $this->orderTransactionRepository->create([
                'transaction_id' => $payload['id'] ?? 'emis-'.$orderId,
                'status'         => $payload['status'] ?? 'ACCEPTED',
                'type'           => $order->payment->method,
                'payment_method' => $order->payment->method,
                'order_id'       => $order->id,
                'invoice_id'     => $invoice?->id,
                'amount'         => $order->base_grand_total,
                'data'           => json_encode($payload),
            ]);

            $cart = $this->cartRepository->find($order->cart_id);

            if ($cart && $cart->is_active) {
                Cart::setCart($cart);
                Cart::deActivateCart();
            }

            Log::channel('single')->info('[EMIS][ETAPA_4_WEBHOOK] Pedido marcado como pago.', [
                'order_id' => $orderId,
            ]);
        } elseif ($newStatus === 'canceled') {
            if (! in_array($order->status, ['processing', 'completed'], true)) {
                $this->orderRepository->update(['status' => 'canceled'], $orderId);
            }

            Log::channel('single')->warning('[EMIS][ETAPA_4_WEBHOOK] Pagamento cancelado ou rejeitado.', [
                'order_id' => $orderId,
                'status'   => $emisStatus,
            ]);
        } else {
            Log::channel('single')->warning('[EMIS][ETAPA_4_WEBHOOK] Status EMIS nao reconhecido.', [
                'order_id' => $orderId,
                'status'   => $emisStatus,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function test(Request $request): JsonResponse
    {
        Log::channel('single')->info('[EMIS][DIAGNOSTICO] Endpoint /test chamado.', [
            'ip'     => $request->ip(),
            'method' => $request->method(),
        ]);

        return response()->json([
            'ok'          => true,
            'version'     => '1.0.0',
            'message'     => 'Endpoint EMIS Payment acessivel e a funcionar.',
            'webhook_url' => route('emis_payment.webhook'),
        ]);
    }

    protected function findActiveOrderByCartId(int $cartId)
    {
        return $this->orderRepository
            ->scopeQuery(fn ($query) => $query->where('cart_id', $cartId)->whereIn('status', ['pending', 'processing']))
            ->first();
    }

    protected function findOrder(int $orderId)
    {
        try {
            return $this->orderRepository->find($orderId);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function prepareInvoiceData($order): array
    {
        $invoiceData = ['order_id' => $order->id];

        foreach ($order->items as $item) {
            $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
        }

        return $invoiceData;
    }

    protected function mergePaymentAdditional($order, array $additional): void
    {
        $order->payment->update([
            'additional' => array_filter(array_merge($order->payment->additional ?? [], $additional), fn ($value) => $value !== null),
        ]);
    }

    protected function extractWebhookPayload(Request $request, string $rawBody): array
    {
        if ($rawBody !== '') {
            $json = json_decode($rawBody, true);

            if (is_array($json) && json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }

        $payload = array_filter($request->request->all(), fn ($value) => $value !== null && $value !== '');

        if ($payload !== []) {
            return $payload;
        }

        return array_filter($request->query(), fn ($value) => $value !== null && $value !== '');
    }

    protected function extractMerchantReference(array $payload): string
    {
        $reference = $payload['merchantReferenceNumber']
            ?? $payload['merchantReference']
            ?? $payload['referenceNumber']
            ?? $payload['reference']
            ?? $payload['orderReference']
            ?? null;

        if (is_array($reference)) {
            $reference = $reference['id']
                ?? $reference['reference']
                ?? $reference['number']
                ?? '';
        }

        return (string) $reference;
    }

    protected function extractPaymentStatus(array $payload): string
    {
        return (string) (
            $payload['status']
            ?? $payload['paymentStatus']
            ?? $payload['transactionStatus']
            ?? $payload['result']
            ?? ''
        );
    }
}

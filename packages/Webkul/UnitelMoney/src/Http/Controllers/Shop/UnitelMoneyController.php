<?php

namespace Webkul\UnitelMoney\Http\Controllers\Shop;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\OrderTransactionRepository;
use Webkul\Sales\Transformers\OrderResource;
use Webkul\UnitelMoney\Repositories\UnitelMoneyLogRepository;
use Webkul\UnitelMoney\Services\UnitelMoneyApiClient;
use Webkul\UnitelMoney\Services\UnitelMoneyPayloadMasker;
use Webkul\UnitelMoney\Services\UnitelMoneyResultMapper;

class UnitelMoneyController extends Controller
{
    public function __construct(
        protected CartRepository $cartRepository,
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository,
        protected OrderTransactionRepository $orderTransactionRepository,
        protected UnitelMoneyLogRepository $logRepository,
        protected UnitelMoneyApiClient $apiClient,
        protected UnitelMoneyResultMapper $resultMapper,
        protected UnitelMoneyPayloadMasker $masker
    ) {}

    public function redirect(): RedirectResponse|View
    {
        $cart = Cart::getCart();

        if (! $cart || $cart->payment?->method !== 'unitel_money') {
            session()->flash('error', trans('unitel_money::app.messages.invalid-cart'));

            return redirect()->route('shop.checkout.cart.index');
        }

        try {
            Cart::collectTotals();

            $order = $this->findActiveOrderByCartId($cart->id);

            if (! $order) {
                $order = $this->orderRepository->create((new OrderResource($cart))->jsonSerialize());
            }

            $result = $this->apiClient->initiatePayment($cart, $order);
            $identifiers = $this->resultMapper->extractIdentifiers($result['response'] ?? []);

            $this->mergePaymentAdditional($order, [
                'unitel_money_request'                    => $this->apiClient->masked($result['request']),
                'unitel_money_response'                   => $this->apiClient->masked($result['response']),
                'unitel_money_originator_conversation_id' => $identifiers['originator_conversation_id']
                    ?? $result['request']['OriginatorConversationID']
                    ?? null,
                'unitel_money_conversation_id' => $identifiers['conversation_id'] ?? null,
                'unitel_money_status'          => 'initiated',
            ]);

            $this->log('Payment initiated', 'initiated', $order->id, $cart->id, $result['response'], [
                'originator_conversation_id' => $identifiers['originator_conversation_id'] ?? $result['request']['OriginatorConversationID'] ?? null,
                'conversation_id'            => $identifiers['conversation_id'] ?? null,
            ]);

            return view('unitel_money::shop.waiting', compact('order'));
        } catch (\Throwable $e) {
            report($e);

            session()->flash('error', trans('unitel_money::app.messages.initiation-failed'));

            return redirect()->route('shop.checkout.cart.index');
        }
    }

    public function callback(string $token): JsonResponse
    {
        if (! $this->isValidCallbackToken($token) || ! $this->isAllowedIp()) {
            return response()->json(['status' => 'forbidden'], 403);
        }

        $payload = request()->json()->all() ?: request()->all();
        $context = $this->resultMapper->map($payload);
        $identifiers = $this->resultMapper->extractIdentifiers($payload);

        $order = $this->resolveOrder($identifiers);

        if (! $order) {
            $this->log('Callback received without matching order', 'orphan', null, $identifiers['cart_id'] ?? null, $payload, $context + $identifiers);

            return response()->json(['status' => 'order_not_found'], 200);
        }

        if ($this->transactionExists($identifiers['transaction_id'] ?? null)) {
            return response()->json(['status' => 'already_processed'], 200);
        }

        $this->applyPaymentResult($order, $context, $payload, $identifiers);

        return response()->json(['status' => 'ok'], 200);
    }

    public function queryStatus(int $orderId): RedirectResponse
    {
        $order = $this->orderRepository->findOrFail($orderId);
        $originatorConversationId = $order->payment->additional['unitel_money_originator_conversation_id'] ?? null;

        if (! $originatorConversationId) {
            session()->flash('error', trans('unitel_money::app.messages.missing-reference'));

            return redirect()->back();
        }

        try {
            $payload = $this->apiClient->queryTransactionStatus($originatorConversationId);
            $context = $this->resultMapper->map($payload);
            $identifiers = $this->resultMapper->extractIdentifiers($payload);

            if (! $this->transactionExists($identifiers['transaction_id'] ?? null)) {
                $this->applyPaymentResult($order, $context, $payload, $identifiers);
            }

            session()->flash('success', trans('unitel_money::app.messages.query-complete'));
        } catch (\Throwable $e) {
            report($e);

            session()->flash('error', trans('unitel_money::app.messages.query-failed'));
        }

        return redirect()->back();
    }

    protected function applyPaymentResult($order, array $context, array $payload, array $identifiers): void
    {
        $this->mergePaymentAdditional($order, [
            'unitel_money_context'         => $context,
            'unitel_money_callback'        => $this->masker->mask($payload),
            'unitel_money_result_code'     => $context['result_code'],
            'unitel_money_result_desc'     => $context['result_desc'],
            'unitel_money_reason_key'      => $context['reason_key'],
            'unitel_money_template_key'    => $context['template_key'],
            'unitel_money_status_group'    => $context['status_group'],
            'unitel_money_transaction_id'  => $identifiers['transaction_id'] ?? null,
            'unitel_money_conversation_id' => $identifiers['conversation_id'] ?? null,
        ]);

        if ($context['status_group'] === 'success') {
            $this->orderRepository->update(['status' => 'processing'], $order->id);

            $invoice = $order->canInvoice()
                ? $this->invoiceRepository->create($this->prepareInvoiceData($order), $context['invoice_state'], $context['order_status'])
                : null;

            $this->orderTransactionRepository->create([
                'transaction_id'  => $identifiers['transaction_id'] ?? $identifiers['conversation_id'] ?? $identifiers['originator_conversation_id'] ?? 'unitel-money-'.$order->id,
                'status'          => $context['result_code'],
                'type'            => $order->payment->method,
                'payment_method'  => $order->payment->method,
                'order_id'        => $order->id,
                'invoice_id'      => $invoice?->id,
                'amount'          => $order->base_grand_total,
                'data'            => json_encode($this->masker->mask($payload)),
            ]);

            $cart = $this->cartRepository->find($order->cart_id);

            if ($cart && $cart->is_active) {
                Cart::setCart($cart);
                Cart::deActivateCart();
            }
        } else {
            $this->orderRepository->update(['status' => $context['order_status']], $order->id);
        }

        $this->log('Payment result applied', $context['status_group'], $order->id, $order->cart_id, $payload, $context + $identifiers);

        Event::dispatch('unitel_money.result.mapped', [$order, $context, $payload]);
        Event::dispatch('unitel_money.result.applied', [$order, $context, $payload]);
    }

    protected function prepareInvoiceData($order): array
    {
        $invoiceData = ['order_id' => $order->id];

        foreach ($order->items as $item) {
            $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
        }

        return $invoiceData;
    }

    protected function resolveOrder(array $identifiers)
    {
        if (! empty($identifiers['cart_id'])) {
            return $this->orderRepository->findOneWhere(['cart_id' => $identifiers['cart_id']]);
        }

        foreach (['originator_conversation_id', 'conversation_id', 'transaction_id'] as $key) {
            if (empty($identifiers[$key])) {
                continue;
            }

            $order = $this->orderRepository
                ->scopeQuery(fn ($query) => $query->whereHas('payment', fn ($paymentQuery) => $paymentQuery->where('additional', 'like', '%'.$identifiers[$key].'%')))
                ->first();

            if ($order) {
                return $order;
            }
        }

        return null;
    }

    protected function findActiveOrderByCartId(int $cartId)
    {
        return $this->orderRepository
            ->scopeQuery(fn ($query) => $query->where('cart_id', $cartId)->whereIn('status', ['pending', 'processing']))
            ->first();
    }

    protected function transactionExists(?string $transactionId): bool
    {
        if (! $transactionId) {
            return false;
        }

        return $this->orderTransactionRepository->findOneWhere(['transaction_id' => $transactionId]) !== null;
    }

    protected function mergePaymentAdditional($order, array $additional): void
    {
        $order->payment->update([
            'additional' => array_filter(array_merge($order->payment->additional ?? [], $additional), fn ($value) => $value !== null),
        ]);
    }

    protected function log(string $title, string $status, ?int $orderId, ?int $cartId, array $payload, array $context = []): void
    {
        $this->logRepository->create([
            'event_uid'                  => $context['transaction_id'] ?? $context['conversation_id'] ?? $context['originator_conversation_id'] ?? uniqid('unitel_', true),
            'order_id'                   => $orderId,
            'cart_id'                    => $cartId,
            'originator_conversation_id' => $context['originator_conversation_id'] ?? null,
            'conversation_id'            => $context['conversation_id'] ?? null,
            'transaction_id'             => $context['transaction_id'] ?? null,
            'status'                     => $status,
            'title'                      => $title,
            'message'                    => $context['admin_reason'] ?? null,
            'payload'                    => $this->masker->mask($payload),
            'context'                    => $context,
        ]);
    }

    protected function isValidCallbackToken(string $token): bool
    {
        $configuredToken = core()->getConfigData('sales.payment_methods.unitel_money.callback_secret');

        return $configuredToken && hash_equals((string) $configuredToken, $token);
    }

    protected function isAllowedIp(): bool
    {
        $allowedIps = trim((string) core()->getConfigData('sales.payment_methods.unitel_money.allowed_ips'));

        if ($allowedIps === '') {
            return true;
        }

        return in_array(request()->ip(), array_map('trim', preg_split('/[\s,]+/', $allowedIps)), true);
    }
}

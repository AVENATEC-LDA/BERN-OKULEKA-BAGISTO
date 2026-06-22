<?php

namespace Avenatec\EmisPayment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\OrderRepository;
use Avenatec\EmisPayment\Payment\EmisPayment;

class EmisController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected EmisPayment     $emisPayment,
    ) {}

    // =========================================================================
    // ETAPA 1 — REDIRECT PÓS-CHECKOUT
    // O Bagisto redireciona aqui após o cliente confirmar a encomenda.
    // =========================================================================
    public function redirect(Request $request)
    {
        // Recuperar o pedido criado pelo Bagisto (guardado na sessão pelo checkout)
        $order = $this->getLastOrder();

        if (!$order) {
            Log::error('[EMIS][ETAPA_1] Pedido não encontrado após redirect do checkout.');
            return redirect()->route('shop.checkout.cart.index')
                ->with('error', 'Sessão de pagamento inválida. Por favor tente novamente.');
        }

        Log::info('[EMIS][ETAPA_1] Checkout iniciado.', [
            'order_id'    => $order->id,
            'total'       => $order->grand_total,
            'currency'    => $order->order_currency_code,
            'email'       => $order->customer_email,
        ]);

        // URL do webhook — EMIS vai POST aqui com o resultado
        $callbackUrl = route('emis_payment.webhook');

        try {
            // ── ETAPA 2: Solicitar token frame à EMIS ──────────────────────
            $frame = $this->emisPayment->requestFrameToken(
                $order->id,
                (float) $order->grand_total,
                $callbackUrl
            );

            // Guardar frame_id na sessão para a página de pagamento
            session([
                'emis_frame_id'  => $frame['id'],
                'emis_order_id'  => $order->id,
                'emis_order_key' => $order->cart_id ?? $order->id,
            ]);

            Log::info('[EMIS][ETAPA_1] Token obtido. A redirecionar para página de pagamento.', [
                'order_id'    => $order->id,
                'frame_id'    => $this->emisPayment->mask($frame['id']),
                'payment_url' => route('emis_payment.pay'),
            ]);

            return redirect()->route('emis_payment.pay');

        } catch (\Exception $e) {
            Log::error('[EMIS][ETAPA_1] Erro ao obter token EMIS.', [
                'order_id' => $order->id ?? null,
                'erro'     => $e->getMessage(),
            ]);

            return redirect()->route('shop.checkout.cart.index')
                ->with('error', 'Erro ao iniciar pagamento: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // ETAPA 3 — PÁGINA FULLSCREEN COM IFRAME EMIS
    // =========================================================================
    public function pay(Request $request)
    {
        $frameId = session('emis_frame_id');
        $orderId = session('emis_order_id');

        if (!$frameId || !$orderId) {
            Log::warning('[EMIS][ETAPA_3] Sessão de pagamento inválida ou expirada.');
            return redirect()->route('shop.checkout.cart.index')
                ->with('error', 'Sessão de pagamento expirada. Por favor tente novamente.');
        }

        $order = $this->orderRepository->find($orderId);

        if (!$order) {
            return redirect()->route('shop.checkout.cart.index')
                ->with('error', 'Pedido não encontrado.');
        }

        // Se já estiver pago, redirecionar para confirmação
        if (in_array($order->status, ['processing', 'completed'], true)) {
            session()->forget(['emis_frame_id', 'emis_order_id', 'emis_order_key']);
            return redirect()->route('shop.checkout.success');
        }

        $frameHost = $this->emisPayment->getConfig('frame_host')
            ?: 'https://pagamentonline.emis.co.ao/online-payment-gateway/portal/frame?token=';

        $iframeSrc    = $frameHost . rawurlencode($frameId);
        $successUrl   = route('shop.checkout.success');
        $cancelUrl    = route('shop.checkout.cart.index');
        $storeName    = config('app.name', 'Loja Online');
        $logoUrl      = core()->getCurrentChannel()->logo_url ?? '';
        $orderTotal   = number_format((float) $order->grand_total, 2, '.', '') . ' ' . $order->order_currency_code;

        Log::info('[EMIS][ETAPA_3] Página de pagamento fullscreen carregada.', [
            'order_id'  => $orderId,
            'frame_id'  => $this->emisPayment->mask($frameId),
            'iframe_src'=> $iframeSrc,
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

    // =========================================================================
    // ETAPA 4 — WEBHOOK DA EMIS (servidor → servidor)
    // A EMIS faz POST aqui com o resultado da transacção.
    // =========================================================================
    public function webhook(Request $request)
    {
        Log::info('[EMIS][ETAPA_4_WEBHOOK] POST recebido.', [
            'hora'       => now()->toDateTimeString(),
            'ip'         => $request->ip(),
            'body_bruto' => $request->getContent(),
        ]);

        $json = $request->json()->all();

        if (empty($json)) {
            Log::error('[EMIS][ETAPA_4_WEBHOOK] Body inválido ou não é JSON.');
            return response()->json(['ok' => false, 'erro' => 'json_invalido'], 400);
        }

        // ── Extrair campos do payload EMIS ────────────────────────────────
        $merchantRef = $json['merchantReferenceNumber']
            ?? $json['reference']['id']
            ?? (is_string($json['reference'] ?? null) ? $json['reference'] : null)
            ?? '';

        $txid      = $json['id']           ?? '';
        $status    = $json['status']       ?? '';
        $errorMsg  = $json['errorMessage'] ?? '';
        $amount    = $json['amount']       ?? 0;
        $currency  = $json['currency']     ?? '';

        Log::info('[EMIS][ETAPA_4_WEBHOOK] Campos extraídos.', [
            'merchantRef' => $merchantRef,
            'txid'        => $txid,
            'status'      => $status,
            'errorMsg'    => $errorMsg ?: '(nenhum)',
            'amount'      => $amount,
            'currency'    => $currency,
        ]);

        // ── Extrair order_id da referência (ex: "BERNO1234" → 1234) ──────
        if (!preg_match('/(\d+)$/', $merchantRef, $matches)) {
            Log::error('[EMIS][ETAPA_4_WEBHOOK] Não foi possível extrair order_id.', ['merchantRef' => $merchantRef]);
            return response()->json(['ok' => false, 'erro' => 'referencia_invalida'], 400);
        }

        $orderId = (int) $matches[1];
        $order   = $this->orderRepository->find($orderId);

        if (!$order) {
            Log::error('[EMIS][ETAPA_4_WEBHOOK] Pedido não encontrado.', ['order_id' => $orderId]);
            return response()->json(['ok' => false, 'erro' => 'pedido_nao_encontrado'], 404);
        }

        Log::info('[EMIS][ETAPA_4_WEBHOOK] Pedido identificado.', [
            'order_id'   => $orderId,
            'status_emis'=> strtoupper($status),
            'status_wc'  => $order->status,
        ]);

        // ── Actualizar status do pedido ───────────────────────────────────
        $newStatus = $this->emisPayment->resolveOrderStatus($status);

        if ($newStatus === 'processing') {
            // Já pago — não duplicar
            if (in_array($order->status, ['processing', 'completed'], true)) {
                Log::warning('[EMIS][ETAPA_4_WEBHOOK] Pedido já estava pago. Ignorado.', ['order_id' => $orderId]);
                return response()->json(['ok' => true, 'nota' => 'ja_pago']);
            }

            $this->orderRepository->update(['status' => 'processing'], $orderId);

            // Registar pagamento no Bagisto
            app(\Webkul\Sales\Repositories\InvoiceRepository::class)->create([
                'order_id' => $orderId,
            ]);

            Log::info('[EMIS][ETAPA_4_WEBHOOK] ✅ PEDIDO MARCADO COMO PAGO.', [
                'order_id'  => $orderId,
                'txid'      => $txid,
                'novo_status' => 'processing',
            ]);

        } elseif ($newStatus === 'canceled') {
            if (!in_array($order->status, ['processing', 'completed'], true)) {
                $this->orderRepository->update(['status' => 'canceled'], $orderId);

                Log::warning('[EMIS][ETAPA_4_WEBHOOK] ❌ Pagamento rejeitado/cancelado. Pedido → canceled.', [
                    'order_id'    => $orderId,
                    'status_emis' => strtoupper($status),
                    'erro_emis'   => $errorMsg,
                ]);
            }

        } else {
            Log::warning('[EMIS][ETAPA_4_WEBHOOK] ⚠️ Status EMIS não reconhecido.', [
                'order_id'        => $orderId,
                'status_recebido' => strtoupper($status),
                'nota'            => 'Pedido não alterado. Verificar manualmente.',
            ]);
        }

        return response()->json(['ok' => true, 'status_processado' => strtoupper($status)]);
    }

    // =========================================================================
    // DIAGNÓSTICO — /emis-payment/test
    // =========================================================================
    public function test(Request $request)
    {
        Log::info('[EMIS][DIAGNOSTICO] Endpoint /test chamado.', [
            'ip'     => $request->ip(),
            'method' => $request->method(),
            'hora'   => now()->toDateTimeString(),
        ]);

        return response()->json([
            'ok'          => true,
            'versao'      => '1.0.0',
            'mensagem'    => 'Endpoint EMIS Payment acessível e a funcionar.',
            'webhook_url' => route('emis_payment.webhook'),
            'hora'        => now()->toDateTimeString(),
            'metodo'      => $request->method(),
        ]);
    }

    // =========================================================================
    // HELPER — Recuperar último pedido da sessão do Bagisto
    // =========================================================================
    protected function getLastOrder(): ?\Webkul\Sales\Models\Order
    {
        // O Bagisto guarda o order_id na sessão após checkout
        $orderId = session('order_id') ?? session('emis_order_id');

        if ($orderId) {
            return $this->orderRepository->find($orderId);
        }

        // Fallback: último pedido do cliente autenticado
        if (auth()->guard('customer')->check()) {
            return $this->orderRepository
                ->where('customer_id', auth()->guard('customer')->id())
                ->latest()
                ->first();
        }

        return null;
    }
}

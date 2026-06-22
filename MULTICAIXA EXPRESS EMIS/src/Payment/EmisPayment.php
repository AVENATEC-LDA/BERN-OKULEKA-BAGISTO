<?php

namespace Avenatec\EmisPayment\Payment;

use Webkul\Payment\Payment\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EmisPayment extends Payment
{
    /**
     * Código único do gateway — deve corresponder ao key em payment-methods.php
     */
    protected $code = 'emis_payment';

    /**
     * Endpoint da EMIS para obter token de frame
     */
    const EMIS_ENDPOINT_DEFAULT = 'https://pagamentonline.emis.co.ao/online-payment-gateway/portal/frameToken';

    /**
     * Host do iframe EMIS
     */
    const EMIS_FRAME_HOST_DEFAULT = 'https://pagamentonline.emis.co.ao/online-payment-gateway/portal/frame?token=';

    // =========================================================================
    // MÉTODOS OBRIGATÓRIOS DO BAGISTO
    // =========================================================================

    /**
     * URL de redirect após o cliente confirmar o pagamento no checkout.
     * O Bagisto redireciona o cliente para esta rota.
     */
    public function getRedirectUrl(): string
    {
        return route('emis_payment.redirect');
    }

    /**
     * Informações adicionais exibidas no checkout (frontend).
     */
    public function getAdditionalDetails(): array
    {
        return [
            'title'       => $this->getConfig('title') ?: 'Pagamento Online – MULTICAIXA Express ou Pagar Online',
            'description' => $this->getConfig('description') ?: 'Pague com MULTICAIXA Express ou QR Code.',
        ];
    }

    // =========================================================================
    // HELPERS DE CONFIGURAÇÃO
    // =========================================================================

    /**
     * Ler valor de configuração do admin Bagisto.
     */
    public function getConfig(string $field): mixed
    {
        return core()->getConfigData("sales.payment_methods.{$this->code}.{$field}");
    }

    /**
     * Prefixo da referência (ex: "BERNO"), sanitizado e limitado a 6 chars.
     */
    public function getReferencePrefix(): string
    {
        $raw = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($this->getConfig('reference_prefix') ?? 'EMIS'));
        return substr($raw, 0, 6) ?: 'EMIS';
    }

    /**
     * Constrói a referência da transacção: prefixo + order_id (máx 15 chars, requisito EMIS).
     */
    public function buildReference(int $orderId): string
    {
        $ref = $this->getReferencePrefix() . $orderId;
        return substr(preg_replace('/[^A-Za-z0-9]/', '', $ref), 0, 15);
    }

    /**
     * Converte o montante para AOA inteiro (requisito EMIS: inteiro, não string).
     */
    public function toAoa(float $amount): int
    {
        return (int) round($amount);
    }

    /**
     * Mascara valores sensíveis para logs.
     */
    protected function mask(string $value): string
    {
        if (strlen($value) <= 8) return str_repeat('*', strlen($value));
        return substr($value, 0, 4) . str_repeat('*', strlen($value) - 8) . substr($value, -4);
    }

    // =========================================================================
    // ETAPA 2 — PEDIDO DE TOKEN FRAME À EMIS
    // =========================================================================

    /**
     * Solicita um token de sessão à EMIS e devolve o array de resposta.
     * Retorna ['id' => '...', 'timeToLive' => ...] ou lança excepção.
     */
    public function requestFrameToken(int $orderId, float $amount, string $callbackUrl): array
    {
        $endpoint    = $this->getConfig('endpoint') ?: self::EMIS_ENDPOINT_DEFAULT;
        $frameToken  = $this->getConfig('frame_token') ?? '';
        $terminalId  = $this->getConfig('terminal_id') ?? '';
        $mobileMode  = $this->getConfig('mobile_mode') ?: 'PAYMENT';
        $qrcodeMode  = $this->getConfig('qrcode_mode') ?: 'PAYMENT';
        $cardMode    = $this->getConfig('card_mode') ?: 'DISABLED';
        $reference   = $this->buildReference($orderId);

        $payload = [
            'reference'   => $reference,
            'amount'      => $this->toAoa($amount),
            'token'       => $frameToken,
            'mobile'      => $mobileMode,
            'qrCode'      => $qrcodeMode,
            'card'        => $cardMode,
            'callbackUrl' => $callbackUrl,
        ];

        if (!empty($terminalId)) {
            $payload['terminal'] = $terminalId;
        }

        // Log com token mascarado
        $logPayload          = $payload;
        $logPayload['token'] = $this->mask($frameToken);

        Log::channel('single')->info('[EMIS][ETAPA_2] Payload enviado à EMIS:', $logPayload);
        Log::channel('single')->info('[EMIS][ETAPA_2] Endpoint: ' . $endpoint);
        Log::channel('single')->info('[EMIS][ETAPA_2] callbackUrl: ' . $callbackUrl);

        $response = Http::timeout(45)
            ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
            ->post($endpoint, $payload);

        $statusCode = $response->status();
        $body       = $response->json();

        Log::channel('single')->info('[EMIS][ETAPA_2] Resposta da EMIS:', [
            'http_code'   => $statusCode,
            'body'        => $body,
        ]);

        if (!$response->successful() || empty($body['id'])) {
            $msg = $body['message'] ?? $body['error'] ?? 'Resposta inválida da EMIS';
            Log::channel('single')->error('[EMIS][ETAPA_2] EMIS recusou token: ' . $msg);
            throw new \RuntimeException('EMIS: ' . $msg);
        }

        Log::channel('single')->info('[EMIS][ETAPA_2] Token obtido com sucesso.', [
            'frame_id'     => $this->mask($body['id']),
            'time_to_live' => ($body['timeToLive'] ?? 0) / 1000 . ' seg',
        ]);

        return $body;
    }

    // =========================================================================
    // ETAPA 5 — PROCESSAR STATUS DO WEBHOOK
    // =========================================================================

    /**
     * Mapeia o status da EMIS para o status do pedido Bagisto.
     * Retorna o método a chamar no pedido ou null se não reconhecido.
     */
    public function resolveOrderStatus(string $emisStatus): ?string
    {
        $status = strtoupper($emisStatus);

        if (in_array($status, ['ACCEPTED', 'SUCCESS', 'PAID', 'COMPLETED'], true)) {
            return 'processing'; // Pago
        }

        if (in_array($status, ['REJECTED', 'FAILED'], true)) {
            return 'canceled'; // Rejeitado
        }

        if (in_array($status, ['CANCELLED', 'EXPIRED'], true)) {
            return 'canceled'; // Cancelado/expirado
        }

        return null; // Status desconhecido
    }
}

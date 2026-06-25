<?php

namespace Webkul\UnitelMoney\Services;

class UnitelMoneyResultMapper
{
    protected array $failureMap = [
        '401901'  => ['customer_cancelled', 'unitel_money_failed_customer_cancelled'],
        '3007001' => ['insufficient_funds', 'unitel_money_failed_insufficient_funds'],
        '3013001' => ['incorrect_pin_or_security_credential', 'unitel_money_failed_incorrect_pin'],
        '3013002' => ['invalid_or_inactive_wallet', 'unitel_money_failed_invalid_wallet'],
        '3015'    => ['msisdn_verification_failed', 'unitel_money_failed_msisdn'],
    ];

    public function map(array $payload): array
    {
        $node = $this->detectNode($payload);
        $result = $payload[$node] ?? $payload;
        $resultCode = (string) ($result['ResultCode'] ?? $result['resultCode'] ?? $payload['ResultCode'] ?? $payload['resultCode'] ?? '');
        $resultDescription = $result['ResultDesc'] ?? $result['ResultDescription'] ?? $result['resultDesc'] ?? null;

        if ($node === 'ResultBuyGoods' && $resultCode === '0') {
            return $this->context(
                node: $node,
                resultCode: $resultCode,
                resultDescription: $resultDescription,
                reasonKey: 'payment_success',
                templateKey: 'unitel_money_payment_success',
                statusGroup: 'success',
                orderStatus: 'processing',
                invoiceState: 'paid'
            );
        }

        if ($resultCode === '' || $resultCode === 'null') {
            return $this->context(
                node: $node,
                resultCode: $resultCode,
                resultDescription: $resultDescription,
                reasonKey: 'payment_failed',
                templateKey: 'unitel_money_failed_generic',
                statusGroup: 'pending',
                orderStatus: 'pending',
                invoiceState: null
            );
        }

        [$reasonKey, $templateKey] = $this->failureMap[$resultCode] ?? ['payment_failed', 'unitel_money_failed_generic'];

        return $this->context(
            node: $node,
            resultCode: $resultCode,
            resultDescription: $resultDescription,
            reasonKey: $reasonKey,
            templateKey: $templateKey,
            statusGroup: 'failed',
            orderStatus: 'canceled',
            invoiceState: null
        );
    }

    public function extractIdentifiers(array $payload): array
    {
        $flat = $this->flatten($payload);

        return [
            'originator_conversation_id' => $flat['OriginatorConversationID'] ?? $flat['originatorConversationId'] ?? $flat['originator_conversation_id'] ?? null,
            'conversation_id'            => $flat['ConversationID'] ?? $flat['conversationId'] ?? $flat['conversation_id'] ?? null,
            'transaction_id'             => $flat['TransactionID'] ?? $flat['transactionId'] ?? $flat['transaction_id'] ?? null,
            'cart_id'                    => $flat['cart_id'] ?? $flat['CartID'] ?? $flat['ReferenceData.ReferenceItem.Value'] ?? null,
        ];
    }

    protected function context(
        string $node,
        string $resultCode,
        ?string $resultDescription,
        string $reasonKey,
        string $templateKey,
        string $statusGroup,
        string $orderStatus,
        ?string $invoiceState
    ): array {
        return [
            'node'            => $node,
            'result_code'     => $resultCode,
            'result_desc'     => $resultDescription,
            'reason_key'      => $reasonKey,
            'reason_label'    => trans('unitel_money::app.reasons.'.$reasonKey),
            'template_key'    => $templateKey,
            'status_group'    => $statusGroup,
            'customer_reason' => trans('unitel_money::app.customer-reasons.'.$reasonKey),
            'admin_reason'    => trans('unitel_money::app.admin-reasons.'.$reasonKey),
            'order_status'    => $orderStatus,
            'invoice_state'   => $invoiceState,
        ];
    }

    protected function detectNode(array $payload): string
    {
        if (array_key_exists('ResultBuyGoods', $payload)) {
            return 'ResultBuyGoods';
        }

        if (array_key_exists('Result', $payload)) {
            return 'Result';
        }

        return 'Result';
    }

    protected function flatten(array $payload, string $prefix = ''): array
    {
        $result = [];

        foreach ($payload as $key => $value) {
            $nextKey = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $result += $this->flatten($value, $nextKey);
            } else {
                $result[$nextKey] = $value;
                $result[(string) $key] = $value;
            }
        }

        return $result;
    }
}

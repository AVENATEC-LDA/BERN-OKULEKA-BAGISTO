<?php

return [
    'admin' => [
        'system' => [
            'name'                => 'Unitel Money',
            'info'                => 'Gateway de pagamento Unitel Money para Angola.',
            'active'              => 'Estado',
            'title'               => 'Titulo',
            'description'         => 'Descricao',
            'sandbox'             => 'Modo Sandbox',
            'client-id'           => 'Client ID',
            'client-secret'       => 'Client Secret',
            'merchant-id'         => 'Merchant ID',
            'service-code'        => 'Service Code',
            'sandbox-base-url'    => 'URL Base Sandbox',
            'production-base-url' => 'URL Base Producao',
            'oauth-path'          => 'Caminho OAuth',
            'buy-goods-path'      => 'Caminho BuyGoods Async',
            'query-status-path'   => 'Caminho Query Status',
            'callback-secret'     => 'Segredo do Callback',
            'allowed-ips'         => 'IPs Permitidos no Callback',
            'sort'                => 'Ordem',
        ],
    ],

    'shop' => [
        'waiting' => [
            'title'        => 'Confirme o pagamento Unitel Money',
            'message'      => 'Foi enviada uma confirmacao para o seu telefone Unitel Money. Aprove para concluir o pedido.',
            'order'        => 'O pedido #:order esta aguardando confirmacao de pagamento.',
            'back-to-cart' => 'Voltar ao carrinho',
        ],
    ],

    'messages' => [
        'invalid-cart'      => 'Nao foi possivel iniciar o pagamento Unitel Money para este carrinho.',
        'initiation-failed' => 'Nao foi possivel iniciar o pagamento Unitel Money. Tente novamente.',
        'missing-reference' => 'A referencia do pagamento Unitel Money nao foi encontrada.',
        'query-complete'    => 'Consulta de estado Unitel Money concluida.',
        'query-failed'      => 'Nao foi possivel consultar o estado do pagamento Unitel Money.',
    ],

    'reasons' => [
        'payment_success'                      => 'Pagamento aprovado',
        'customer_cancelled'                   => 'Cliente cancelou a confirmacao',
        'insufficient_funds'                   => 'Saldo insuficiente',
        'incorrect_pin_or_security_credential' => 'PIN ou credencial de seguranca incorreta',
        'invalid_or_inactive_wallet'           => 'Carteira invalida ou inativa',
        'msisdn_verification_failed'           => 'Falha na validacao do MSISDN',
        'payment_failed'                       => 'Pagamento falhou',
    ],

    'customer-reasons' => [
        'payment_success'                      => 'O seu pagamento Unitel Money foi aprovado.',
        'customer_cancelled'                   => 'Cancelou ou nao aprovou a confirmacao Unitel Money.',
        'insufficient_funds'                   => 'A sua carteira Unitel Money nao tem saldo suficiente.',
        'incorrect_pin_or_security_credential' => 'O PIN ou credencial de seguranca esta incorreto.',
        'invalid_or_inactive_wallet'           => 'A carteira Unitel Money e invalida ou esta inativa.',
        'msisdn_verification_failed'           => 'O numero de telefone nao foi validado pela Unitel Money.',
        'payment_failed'                       => 'O pagamento Unitel Money falhou.',
    ],

    'admin-reasons' => [
        'payment_success'                      => 'A Unitel Money aprovou o pagamento.',
        'customer_cancelled'                   => 'A Unitel Money retornou 401901: cliente cancelou ou abandonou a confirmacao.',
        'insufficient_funds'                   => 'A Unitel Money retornou 3007001: saldo insuficiente.',
        'incorrect_pin_or_security_credential' => 'A Unitel Money retornou 3013001: PIN ou credencial de seguranca incorreta.',
        'invalid_or_inactive_wallet'           => 'A Unitel Money retornou 3013002: carteira invalida ou inativa.',
        'msisdn_verification_failed'           => 'A Unitel Money retornou 3015: falha na validacao do MSISDN.',
        'payment_failed'                       => 'A Unitel Money retornou uma falha nao mapeada.',
    ],
];

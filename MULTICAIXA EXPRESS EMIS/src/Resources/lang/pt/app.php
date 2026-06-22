<?php

return [
    'admin' => [
        'system' => [
            'emis-payment'              => 'EMIS – Multicaixa Express / Pagar Online',
            'emis-payment-info'         => 'Gateway de pagamento EMIS GPO para o mercado angolano.',

            'title'                     => 'Título',
            'description'               => 'Descrição',
            'status'                    => 'Activo',
            'reference-prefix'          => 'Prefixo da Referência',
            'reference-prefix-info'     => 'Até 6 caracteres alfanuméricos. Ex: "BERNO" + 1234 = "BERNO1234".',
            'frame-token'               => 'Frame Token',
            'frame-token-info'          => 'Token do comerciante — obtido no Portal EMIS → ícone Quiosque.',
            'terminal-id'               => 'ID do Terminal (POS)',
            'terminal-id-info'          => 'ID do Point-of-Sale — fornecido pela equipa EMIS/MCX.',
            'mobile-mode'               => 'MULTICAIXA Express (mobile)',
            'qrcode-mode'               => 'QR Code',
            'card-mode'                 => 'Cartão Multicaixa',
            'card-mode-info'            => '⚠️ Requer certificação EGR — não activar sem autorização EMIS.',
            'endpoint'                  => 'Endpoint EMIS (não alterar)',
            'frame-host'                => 'Host do iframe EMIS (não alterar)',
            'sort-order'                => 'Ordem de exibição',
        ],
    ],
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuração do Admin — EMIS Payment
    |--------------------------------------------------------------------------
    | Define os campos visíveis em:
    | Admin → Configurações → Configuração → Vendas → Métodos de Pagamento → EMIS
    */
    [
        'key'  => 'sales.payment_methods.emis_payment',
        'name' => 'emis-payment::app.admin.system.emis-payment',
        'info' => 'emis-payment::app.admin.system.emis-payment-info',
        'sort' => 2,

        'fields' => [

            // ── Activar/Desactivar ─────────────────────────────────────────
            [
                'name'          => 'title',
                'title'         => 'emis-payment::app.admin.system.title',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => true,
            ],

            [
                'name'          => 'description',
                'title'         => 'emis-payment::app.admin.system.description',
                'type'          => 'textarea',
                'channel_based' => false,
                'locale_based'  => true,
            ],

            [
                'name'          => 'active',
                'title'         => 'emis-payment::app.admin.system.status',
                'type'          => 'boolean',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => false,
            ],

            // ── Prefixo da Referência ─────────────────────────────────────
            [
                'name'          => 'reference_prefix',
                'title'         => 'emis-payment::app.admin.system.reference-prefix',
                'type'          => 'text',
                'validation'    => 'required|max:6|alpha_num',
                'channel_based' => false,
                'locale_based'  => false,
                'info'          => 'emis-payment::app.admin.system.reference-prefix-info',
            ],

            // ── Credenciais EMIS ──────────────────────────────────────────
            [
                'name'          => 'frame_token',
                'title'         => 'emis-payment::app.admin.system.frame-token',
                'type'          => 'password',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => false,
                'info'          => 'emis-payment::app.admin.system.frame-token-info',
            ],

            [
                'name'          => 'terminal_id',
                'title'         => 'emis-payment::app.admin.system.terminal-id',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => false,
                'info'          => 'emis-payment::app.admin.system.terminal-id-info',
            ],

            // ── Modos de pagamento ────────────────────────────────────────
            [
                'name'          => 'mobile_mode',
                'title'         => 'emis-payment::app.admin.system.mobile-mode',
                'type'          => 'select',
                'options'       => [
                    ['title' => 'PAYMENT',       'value' => 'PAYMENT'],
                    ['title' => 'AUTHORIZATION', 'value' => 'AUTHORIZATION'],
                    ['title' => 'DISABLED',      'value' => 'DISABLED'],
                ],
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => false,
            ],

            [
                'name'          => 'qrcode_mode',
                'title'         => 'emis-payment::app.admin.system.qrcode-mode',
                'type'          => 'select',
                'options'       => [
                    ['title' => 'PAYMENT',       'value' => 'PAYMENT'],
                    ['title' => 'AUTHORIZATION', 'value' => 'AUTHORIZATION'],
                    ['title' => 'DISABLED',      'value' => 'DISABLED'],
                ],
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => false,
            ],

            [
                'name'          => 'card_mode',
                'title'         => 'emis-payment::app.admin.system.card-mode',
                'type'          => 'select',
                'options'       => [
                    ['title' => 'DISABLED',      'value' => 'DISABLED'],
                    ['title' => 'AUTHORIZATION', 'value' => 'AUTHORIZATION'],
                ],
                'validation'    => 'required',
                'channel_based' => false,
                'locale_based'  => false,
                'info'          => 'emis-payment::app.admin.system.card-mode-info',
            ],

            // ── Endpoints (não alterar) ───────────────────────────────────
            [
                'name'          => 'endpoint',
                'title'         => 'emis-payment::app.admin.system.endpoint',
                'type'          => 'text',
                'validation'    => 'required|url',
                'channel_based' => false,
                'locale_based'  => false,
            ],

            [
                'name'          => 'frame_host',
                'title'         => 'emis-payment::app.admin.system.frame-host',
                'type'          => 'text',
                'validation'    => 'required|url',
                'channel_based' => false,
                'locale_based'  => false,
            ],

            // ── Outras configurações ──────────────────────────────────────
            [
                'name'          => 'sort_order',
                'title'         => 'emis-payment::app.admin.system.sort-order',
                'type'          => 'text',
                'validation'    => 'numeric',
                'channel_based' => false,
                'locale_based'  => false,
            ],
        ],
    ],
];

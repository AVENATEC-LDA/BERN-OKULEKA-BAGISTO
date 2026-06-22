<?php

return [
    /*
    |--------------------------------------------------------------------------
    | EMIS GPO — Multicaixa Express / Pagar Online
    |--------------------------------------------------------------------------
    | Registo do método de pagamento EMIS no Bagisto.
    | O 'code' deve ser único e corresponder ao usado em system.php.
    */
    'emis_payment' => [
        'code'        => 'emis_payment',
        'title'       => 'Pagamento Online – MULTICAIXA Express ou Pagar Online',
        'description' => 'Pague com MULTICAIXA Express, QR Code ou Pagar Online. Processado de forma segura pela EMIS.',
        'class'       => 'Avenatec\EmisPayment\Payment\EmisPayment',
        'active'      => true,
        'sort'        => 1,
    ],
];

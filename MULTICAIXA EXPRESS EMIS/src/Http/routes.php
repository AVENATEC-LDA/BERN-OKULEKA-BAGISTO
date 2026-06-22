<?php

use Illuminate\Support\Facades\Route;
use Avenatec\EmisPayment\Http\Controllers\EmisController;

/*
|--------------------------------------------------------------------------
| Rotas EMIS Payment
|--------------------------------------------------------------------------
|
| redirect  — Bagisto redireciona aqui após checkout; obtém token e serve iframe
| payment   — Página fullscreen com iframe EMIS (GET com token na session)
| webhook   — EMIS faz POST aqui com o resultado da transacção (público)
| test      — Diagnóstico: verifica acessibilidade do endpoint
|
*/

// Rota de redirect pós-checkout — Bagisto chama esta rota
Route::get('emis-payment/redirect', [EmisController::class, 'redirect'])
    ->name('emis_payment.redirect');

// Página fullscreen de pagamento (iframe EMIS)
Route::get('emis-payment/pay', [EmisController::class, 'pay'])
    ->name('emis_payment.pay');

// Webhook — EMIS POST aqui após pagamento (sem CSRF)
Route::post('emis-payment/webhook', [EmisController::class, 'webhook'])
    ->name('emis_payment.webhook')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Endpoint de diagnóstico — verificar acessibilidade
Route::get('emis-payment/test', [EmisController::class, 'test'])
    ->name('emis_payment.test');

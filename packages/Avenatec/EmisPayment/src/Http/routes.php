<?php

use Avenatec\EmisPayment\Http\Controllers\EmisController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web']], function () {
    Route::get('emis-payment/redirect', [EmisController::class, 'redirect'])
        ->name('emis_payment.redirect');

    Route::get('emis-payment/pay', [EmisController::class, 'pay'])
        ->name('emis_payment.pay');

    Route::post('emis-payment/webhook', [EmisController::class, 'webhook'])
        ->withoutMiddleware([VerifyCsrfToken::class])
        ->name('emis_payment.webhook');

    Route::get('emis-payment/test', [EmisController::class, 'test'])
        ->name('emis_payment.test');
});

<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Webkul\UnitelMoney\Http\Controllers\Shop\UnitelMoneyController;

Route::group([
    'middleware' => ['web'],
    'prefix'     => 'unitel-money',
], function () {
    Route::get('redirect', [UnitelMoneyController::class, 'redirect'])
        ->name('unitel-money.redirect');

    Route::match(['put', 'post'], 'callback/{token}', [UnitelMoneyController::class, 'callback'])
        ->withoutMiddleware([VerifyCsrfToken::class])
        ->name('unitel-money.callback');

    Route::post('query-status/{orderId}', [UnitelMoneyController::class, 'queryStatus'])
        ->name('unitel-money.query-status');
});

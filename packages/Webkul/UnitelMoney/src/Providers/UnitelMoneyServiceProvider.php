<?php

namespace Webkul\UnitelMoney\Providers;

use Illuminate\Support\ServiceProvider;

class UnitelMoneyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/payment-methods.php', 'payment_methods');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/system.php', 'core');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/unitel-money.php', 'unitel_money');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Routes/shop-routes.php');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'unitel_money');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'unitel_money');
    }
}

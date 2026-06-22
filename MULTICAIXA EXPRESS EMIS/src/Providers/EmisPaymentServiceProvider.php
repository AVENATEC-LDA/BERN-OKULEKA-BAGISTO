<?php

namespace Avenatec\EmisPayment\Providers;

use Illuminate\Support\ServiceProvider;

class EmisPaymentServiceProvider extends ServiceProvider
{
    /**
     * Register — mescla configurações no arranque da aplicação.
     */
    public function register(): void
    {
        // Regista o método de pagamento no Bagisto
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/payment-methods.php',
            'payment_methods'
        );

        // Regista as configurações do painel de administração
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/system.php',
            'core'
        );
    }

    /**
     * Boot — carrega rotas e views após todos os providers estarem registados.
     */
    public function boot(): void
    {
        // Rotas: página fullscreen + webhook
        $this->loadRoutesFrom(dirname(__DIR__) . '/Http/routes.php');

        // Views Blade
        $this->loadViewsFrom(
            dirname(__DIR__) . '/Resources/views',
            'emis-payment'
        );

        // Traduções
        $this->loadTranslationsFrom(
            dirname(__DIR__) . '/Resources/lang',
            'emis-payment'
        );
    }
}

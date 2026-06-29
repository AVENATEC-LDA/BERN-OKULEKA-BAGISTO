<?php

namespace Webkul\Suggestion\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Suggestion\Console\Commands\Install;

class SuggestionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/system.php', 'core'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/bagisto-vite.php',
            'bagisto-vite.viters'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/../Routes/web.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'suggestion');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'suggestion');

        $this->loadPublishables();

        $this->app->register(EventServiceProvider::class);

        $this->app->runningInConsole() && $this->commands(Install::class);

    }

    /**
     * Load publishable files.
     */
    private function loadPublishables(): void
    {
        $this->publishes([
            __DIR__.'/../../publishable' => public_path('themes/suggestion'),
        ], 'public');

        $this->publishes([
            __DIR__.'/../Resources/views/shop' => resource_path('themes/default/views'),
        ]);

    }
}

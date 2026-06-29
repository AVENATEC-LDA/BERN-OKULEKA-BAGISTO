<?php

namespace Webkul\Suggestion\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Event::listen('bagisto.shop.layout.head.before', function ($event) {
            $event->addTemplate('suggestion::components.layouts.style');
        });
    }
}

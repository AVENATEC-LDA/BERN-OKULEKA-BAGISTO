<?php

namespace App\Providers;

use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $allowedIPs = array_map('trim', explode(',', config('app.debug_allowed_ips', '')));

        $allowedIPs = array_filter($allowedIPs);

        if (empty($allowedIPs)) {
            return;
        }

        if (in_array(Request::ip(), $allowedIPs)) {
            Debugbar::enable();
        } else {
            Debugbar::disable();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Queue::failing(function (JobFailed $event) {
            Log::channel('stderr')->error('Queue job failed.', [
                'connection' => $event->connectionName,
                'queue'      => $event->job->getQueue(),
                'job'        => $event->job->resolveName(),
                'error'      => $event->exception->getMessage(),
                'exception'  => get_class($event->exception),
                'trace'      => $event->exception->getTraceAsString(),
            ]);
        });

        ParallelTesting::setUpTestDatabase(function (string $database, int $token) {
            Artisan::call('db:seed');
        });
    }
}

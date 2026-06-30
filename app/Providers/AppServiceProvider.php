<?php
declare(strict_types=1);

namespace App\Providers;

use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar o OpenGraphService como singleton para uso global
        $this->app->singleton(\App\Services\OpenGraphService::class, function ($app) {
            return new \App\Services\OpenGraphService();
        });

        $rawAllowed = config('app.debug_allowed_ips', '');

        if (is_array($rawAllowed)) {
            $rawAllowed = implode(',', $rawAllowed);
        }

        $rawAllowed = $rawAllowed ?? '';

        $allowedIPs = array_filter(array_map('trim', explode(',', (string) $rawAllowed)));

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
        // Escutar evento de renderização do layout e extrair dados em memória antes de renderizar as metas do cabeçalho
        Event::listen('bagisto.shop.layout.head.after', function ($viewRenderable) {
            try {
                $data = $viewRenderable->getData();
            } catch (\Throwable $e) {
                return null;
            }

            $og = app(\App\Services\OpenGraphService::class);

            $readValue = function ($entity, array $keys) {
                if (is_array($entity)) {
                    foreach ($keys as $k) {
                        if (array_key_exists($k, $entity) && ! empty($entity[$k])) {
                            return $entity[$k];
                        }
                    }

                    return null;
                }

                if (is_object($entity)) {
                    foreach ($keys as $k) {
                        if (property_exists($entity, $k) && ! empty($entity->{$k})) {
                            return $entity->{$k};
                        }

                        if (method_exists($entity, 'getAttribute')) {
                            try {
                                $attr = $entity->getAttribute($k);
                                if (! empty($attr)) {
                                    return $attr;
                                }
                            } catch (\Throwable $e) {
                                // ignore
                            }
                        }

                        if ($entity instanceof \Illuminate\Database\Eloquent\Model && $entity->relationLoaded($k)) {
                            $rel = $entity->{$k};
                            if (is_object($rel) && isset($rel->url)) {
                                return $rel->url;
                            }
                            if (is_string($rel) && ! empty($rel)) {
                                return $rel;
                            }
                        }

                        if (isset($entity->{$k}) && is_object($entity->{$k}) && isset($entity->{$k}->url)) {
                            return $entity->{$k}->url;
                        }
                    }
                }

                return null;
            };

            if (! empty($data['product'])) {
                $product = $data['product'];

                $title = $readValue($product, ['name', 'title']);
                $description = $readValue($product, ['short_description', 'description', 'meta_description']);
                $image = $readValue($product, ['base_image_url', 'image_url', 'image', 'base_image']);

                $og->set($title, $description, $image);

                return null;
            }

            if (! empty($data['category'])) {
                $category = $data['category'];

                $title = $readValue($category, ['name', 'title']);
                $description = $readValue($category, ['meta_description', 'description']);
                $image = $readValue($category, ['image_url', 'image']);

                $og->set($title, $description, $image);

                return null;
            }

            // Suporte genérico para outras entidades passadas pela view
            $genericKeys = ['attributeFamily', 'collection', 'brand'];

            foreach ($genericKeys as $key) {
                if (! empty($data[$key])) {
                    $entity = $data[$key];

                    $title = $readValue($entity, ['name', 'title']);
                    $description = $readValue($entity, ['meta_description', 'description']);
                    $image = $readValue($entity, ['image_url', 'image']);

                    $og->set($title, $description, $image);

                    break;
                }
            }
        });

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

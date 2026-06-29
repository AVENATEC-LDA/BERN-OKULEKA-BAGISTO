<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Request;

/**
 * Service para gerar meta tags Open Graph em memória (singleton).
 * Mantém os valores em memória para evitar consultas adicionais ao banco.
 */
final class OpenGraphService
{
    private ?string $title = null;

    private ?string $description = null;

    private ?string $image = null;

    private ?string $url = null;

    public function __construct()
    {
        $this->url = Request::fullUrl();

        try {
            $channel = core()->getCurrentChannel();
        } catch (\Throwable $e) {
            $channel = null;
        }

        try {
            $settings = core()->getBackEndSettings();
        } catch (\Throwable $e) {
            $settings = [];
        }

        $this->title = $settings['meta_title'] ?? ($channel->name ?? config('app.name'));
        $this->description = $settings['meta_description'] ?? ($channel->home_seo['meta_description'] ?? '');

        // Tentar extrair logo da instância de canal carregada (sem novas queries)
        $this->image = $channel->logo_url ?? $channel->logo ?? $settings['logo'] ?? null;
    }

    public function set(?string $title = null, ?string $description = null, ?string $image = null): void
    {
        if ($title !== null) {
            $this->title = strip_tags($title);
        }

        if ($description !== null) {
            $clean = strip_tags($description);
            $this->description = Str::limit($clean, 160, '');
        }

        if ($image !== null) {
            $this->image = strip_tags($image);
        }
    }

    public function render(): string
    {
        $title = e($this->title ?? '');
        $description = e($this->description ?? '');
        $image = e($this->image ?? '');
        $url = e($this->url ?? url()->to('/'));

        $html = [];

        $html[] = "<meta property=\"og:type\" content=\"website\">";
        $html[] = "<meta property=\"og:url\" content=\"{$url}\">";
        $html[] = "<meta property=\"og:title\" content=\"{$title}\">";
        $html[] = "<meta property=\"og:description\" content=\"{$description}\">";

        if (! empty($image)) {
            $html[] = "<meta property=\"og:image\" content=\"{$image}\">";
        }

        // Twitter cards
        $html[] = "<meta name=\"twitter:card\" content=\"summary_large_image\">";
        $html[] = "<meta name=\"twitter:url\" content=\"{$url}\">";
        $html[] = "<meta name=\"twitter:title\" content=\"{$title}\">";
        $html[] = "<meta name=\"twitter:description\" content=\"{$description}\">";

        if (! empty($image)) {
            $html[] = "<meta name=\"twitter:image\" content=\"{$image}\">";
        }

        return implode("\n", $html);
    }
}

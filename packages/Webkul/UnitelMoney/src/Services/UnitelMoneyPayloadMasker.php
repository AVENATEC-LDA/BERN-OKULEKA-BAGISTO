<?php

namespace Webkul\UnitelMoney\Services;

class UnitelMoneyPayloadMasker
{
    protected array $sensitiveKeys = [
        'access_token',
        'authorization',
        'callback_secret',
        'client_secret',
        'password',
        'pin',
        'secret',
        'token',
    ];

    public function mask(mixed $payload): mixed
    {
        if (is_array($payload)) {
            return $this->maskArray($payload);
        }

        if (is_string($payload)) {
            return $this->maskString($payload);
        }

        return $payload;
    }

    protected function maskArray(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $payload[$key] = '***';

                continue;
            }

            $payload[$key] = $this->mask($value);
        }

        return $payload;
    }

    protected function maskString(string $payload): string
    {
        $payload = preg_replace('/(client_secret|access_token|token|password|pin|secret)(["\']?\s*[:=]\s*["\']?)[^"\'&\s,}]+/i', '$1$2***', $payload);

        return preg_replace('/(?<!\d)(9\d{2})(\d{3})(\d{3})(?!\d)/', '$1***$3', $payload);
    }

    protected function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);

        foreach ($this->sensitiveKeys as $sensitiveKey) {
            if (str_contains($key, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }
}

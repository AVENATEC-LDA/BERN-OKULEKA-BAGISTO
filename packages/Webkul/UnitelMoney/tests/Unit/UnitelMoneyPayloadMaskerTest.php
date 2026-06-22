<?php

use Webkul\UnitelMoney\Services\UnitelMoneyPayloadMasker;

it('masks sensitive array values recursively', function () {
    $payload = app(UnitelMoneyPayloadMasker::class)->mask([
        'client_secret' => 'secret-value',
        'nested' => [
            'access_token' => 'token-value',
            'msisdn' => '923123456',
        ],
    ]);

    expect($payload['client_secret'])->toBe('***')
        ->and($payload['nested']['access_token'])->toBe('***')
        ->and($payload['nested']['msisdn'])->toBe('923***456');
});

it('masks sensitive string values', function () {
    $payload = app(UnitelMoneyPayloadMasker::class)->mask('client_secret=abc123 token: xyz789 phone 923123456');

    expect($payload)
        ->toContain('client_secret=***')
        ->toContain('token: ***')
        ->toContain('923***456')
        ->not->toContain('abc123')
        ->not->toContain('xyz789');
});

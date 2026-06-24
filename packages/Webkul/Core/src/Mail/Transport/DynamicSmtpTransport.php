<?php

namespace Webkul\Core\Mail\Transport;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class DynamicSmtpTransport extends AbstractTransport
{
    /**
     * Send the given message.
     */
    protected function doSend(SentMessage $message): void
    {
        $transport = $this->buildTransport();

        try {
            $transport->send($message->getOriginalMessage(), $message->getEnvelope());
        } catch (\Throwable $e) {
            Log::error('Dynamic SMTP mail delivery failed.', [
                'host'       => $this->value('emails.configure.smtp.host', config('mail.mailers.smtp.host')),
                'port'       => $this->value('emails.configure.smtp.port', config('mail.mailers.smtp.port')),
                'encryption' => $this->value('emails.configure.smtp.encryption', config('mail.mailers.smtp.encryption')),
                'username'   => $this->mask((string) $this->value('emails.configure.smtp.username', config('mail.mailers.smtp.username'))),
                'error'      => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build the SMTP transport from Bagisto core config,
     * falling back to .env / config/mail.php if not set.
     */
    protected function buildTransport(): EsmtpTransport
    {
        $host = $this->value('emails.configure.smtp.host', config('mail.mailers.smtp.host'));
        $port = $this->value('emails.configure.smtp.port', config('mail.mailers.smtp.port'));
        $encryption = $this->value('emails.configure.smtp.encryption', config('mail.mailers.smtp.encryption'));
        $username = $this->value('emails.configure.smtp.username', config('mail.mailers.smtp.username'));
        $password = $this->value('emails.configure.smtp.password', config('mail.mailers.smtp.password'));

        if (! $host) {
            throw new \RuntimeException(
                'Mail SMTP host is not configured. Please set it in Admin → Configuration → Emails → SMTP.'
            );
        }

        $transport = new EsmtpTransport(
            host: $host,
            port: (int) $port,
            tls: strtolower((string) $encryption) === 'ssl',
        );

        $transport->setAutoTls(strtolower((string) $encryption) === 'tls');

        $transport->getStream()->setStreamOptions([
            'ssl' => [
                'crypto_method' => $this->cryptoMethod(),
            ],
        ]);

        $transport->setUsername((string) $username);

        $transport->setPassword((string) $password);

        return $transport;
    }

    /**
     * Read SMTP configuration from Bagisto, falling back when admin values are blank.
     */
    protected function value(string $key, mixed $fallback): mixed
    {
        $value = core()->getConfigData($key);

        return $value !== null && $value !== '' ? $value : $fallback;
    }

    /**
     * ZeptoMail requires TLS 1.2 or newer.
     */
    protected function cryptoMethod(): int
    {
        $method = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;

        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $method |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }

        return $method;
    }

    /**
     * Mask sensitive log context.
     */
    protected function mask(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return substr($value, 0, 4).str_repeat('*', max(strlen($value) - 8, 0)).substr($value, -4);
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'bagisto-dynamic-smtp';
    }
}

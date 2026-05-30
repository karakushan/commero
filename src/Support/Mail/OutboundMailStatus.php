<?php

namespace Commero\Support\Mail;

class OutboundMailStatus
{
    public static function isConfigured(): bool
    {
        $defaultMailer = (string) config('mail.default', 'log');

        if (in_array($defaultMailer, ['array', 'log'], true)) {
            return false;
        }

        if ($defaultMailer !== 'smtp') {
            return true;
        }

        $host = mb_strtolower(trim((string) config('mail.mailers.smtp.host', '')));
        $fromAddress = mb_strtolower(trim((string) config('mail.from.address', '')));

        if ($host === '' || $host === 'mailpit') {
            return false;
        }

        if ($fromAddress === '' || str_ends_with($fromAddress, '@example.com')) {
            return false;
        }

        return true;
    }
}

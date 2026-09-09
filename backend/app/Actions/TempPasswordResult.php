<?php

namespace App\Actions;

/**
 * What happened to one temp-password delivery. Every caller reports from
 * this instead of re-deriving the destination — two of the five used to
 * print the login address while the mail went to the private one.
 */
class TempPasswordResult
{
    public function __construct(
        public readonly string $status,
        /** Where the mail went, or would have gone. */
        public readonly ?string $destination = null,
        /** Mailer exception message; set only on FAILED. */
        public readonly ?string $error = null,
    ) {
    }

    public function sent(): bool
    {
        return $this->status === IssueTempPassword::SENT;
    }

    /**
     * MAIL_MAILER=log writes the message to storage/logs/laravel.log
     * instead of delivering it. The password is real, the delivery is not,
     * so every report has to say so.
     */
    public function loggedNotSent(): bool
    {
        return $this->sent() && config('mail.default') === 'log';
    }
}

<?php

namespace App\Support;

use App\Models\User;

/**
 * The outcome of MemberLogin::provision() — what happened to the account,
 * where the temp password went (if anywhere) and why it didn't, so every
 * call site can report the same thing without re-deriving it.
 */
class MemberLoginResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?User $user = null,
        /** Where the temp password was (or would have been) delivered. */
        public readonly ?string $destination = null,
        /** Mailer exception message, set only on CREATED_MAIL_FAILED. */
        public readonly ?string $error = null,
    ) {
    }

    /** True when this run put a brand-new login behind the roster row. */
    public function createdAccount(): bool
    {
        return in_array($this->status, [
            MemberLogin::CREATED_SENT,
            MemberLogin::CREATED_UNDELIVERABLE,
            MemberLogin::CREATED_MAIL_FAILED,
        ], true);
    }
}

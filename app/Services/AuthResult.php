<?php

namespace App\Services;

use App\Models\User;

/**
 * Outcome of AuthService::attempt(). Shared by the web login and the
 * API token endpoints so both enforce identical rules.
 */
class AuthResult
{
    public const OK = 'ok';

    public const MFA_REQUIRED = 'mfa_required';

    public const INVALID = 'invalid';

    public const INACTIVE = 'inactive';

    public const LOCKED = 'locked';

    public function __construct(
        public readonly string $status,
        public readonly ?User $user = null,
    ) {}

    public function succeeded(): bool
    {
        return $this->status === self::OK;
    }

    public function needsMfa(): bool
    {
        return $this->status === self::MFA_REQUIRED;
    }
}

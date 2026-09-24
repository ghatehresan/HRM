<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * Login identity — intentionally separate from the HR profile
 * (App\Models\Employee, M4): a User is "who can log in", an Employee is
 * "who works here". See ARCHITECTURE.md §3.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_active
 * @property Carbon|null $last_login_at
 * @property Carbon|null $password_changed_at
 * @property bool $mfa_enforced
 * @property int $failed_login_attempts
 * @property Carbon|null $locked_until
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, MfaMethod> $mfaMethods
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'mfa_enforced' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'locked_until' => 'datetime',
            'failed_login_attempts' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * @return HasMany<MfaMethod, $this>
     */
    public function mfaMethods(): HasMany
    {
        return $this->hasMany(MfaMethod::class);
    }

    public function hasRole(string ...$slugs): bool
    {
        return $this->roles()->whereIn('slug', $slugs)->exists();
    }

    /**
     * Grant check used by Gate::before (see AppServiceProvider). The
     * super-admin bypass lives here so model checks, gates, middleware
     * and policies always agree. See AUTHORIZATION.md §1.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', fn (Builder $query) => $query->where('name', $permission))
            ->exists();
    }

    public function primaryMfaMethod(): ?MfaMethod
    {
        return $this->mfaMethods()->where('is_primary', true)->first();
    }

    /**
     * MFA is required when enforced per-user, when the user holds a
     * privileged role (config hrm.auth.mfa_required_roles), or when the
     * user voluntarily enrolled a method (enrollment implies intent).
     */
    public function requiresMfa(): bool
    {
        if ($this->mfa_enforced || $this->primaryMfaMethod() !== null) {
            return true;
        }

        /** @var string[] */
        $roles = (array) config('hrm.auth.mfa_required_roles', []);

        return $roles === [] ? false : $this->hasRole(...$roles);
    }

    public function isLockedOut(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    /**
     * Progressive lockout (SECURITY.md §2): the Nth consecutive failure
     * past the threshold locks the account for an escalating duration.
     */
    public function recordFailedLogin(): void
    {
        $attempts = (int) $this->failed_login_attempts + 1;
        $attributes = ['failed_login_attempts' => $attempts];

        $threshold = (int) config('hrm.auth.lockout_threshold', 5);

        if ($attempts >= $threshold) {
            /** @var int[] */
            $durations = (array) config('hrm.auth.lockout_durations', [3600]);
            $step = min($attempts - $threshold, count($durations) - 1);
            $attributes['locked_until'] = now()->addSeconds((int) ($durations[$step] ?? 3600));
        }

        $this->forceFill($attributes)->save();
    }

    public function clearLoginAttempts(): void
    {
        $this->forceFill(['failed_login_attempts' => 0, 'locked_until' => null])->save();
    }

    /**
     * Persian password-reset notification (replaces the framework default).
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Append-only audit trail (DATABASE.md §3.7, SECURITY.md §1).
 *
 * Hardening, in layers:
 *  1. Schema has no updated_at/deleted_at and no foreign keys.
 *  2. This model cancels every update and throws on delete().
 *  3. No web/API route mutates audit rows (proven by AccessMatrixTest).
 *
 * What this cannot stop: mass deletes issued straight from a console
 * with database access (e.g. `AuditLog::query()->delete()`). That path
 * requires server access and is outside the application threat model.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $event
 * @property string $auditable_type
 * @property int|null $auditable_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $url
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property Carbon|null $created_at
 * @property-read User|null $user
 * @property-read Model|null $auditable
 */
#[Fillable(['user_id', 'event', 'auditable_type', 'auditable_id', 'ip_address', 'user_agent', 'url', 'old_values', 'new_values'])]
class AuditLog extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function delete()
    {
        throw new LogicException('Audit logs are append-only and cannot be deleted.');
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public static function record(
        string $event,
        ?Model $auditable = null,
        ?array $old = null,
        ?array $new = null,
        ?User $user = null,
    ): self {
        $request = request();

        return self::create([
            'user_id' => $user?->getKey() ?? auth()->id(),
            'event' => $event,
            'auditable_type' => $auditable ? $auditable->getMorphClass() : 'system',
            'auditable_id' => $auditable?->getKey(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 1000) : null,
            'url' => $request ? substr($request->fullUrl(), 0, 500) : null,
            'old_values' => $old,
            'new_values' => $new,
        ]);
    }

    /**
     * Admin audit browser (read-only by construction: no other query
     * method on this model mutates rows).
     *
     * @param  array<string, mixed>  $filters
     */
    public static function searchForAdmin(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = self::with('user')->orderByDesc('id');

        if (is_string($filters['event'] ?? null) && $filters['event'] !== '') {
            $query->where('event', $filters['event']);
        }

        if (is_string($filters['email'] ?? null) && trim($filters['email']) !== '') {
            $term = '%'.trim($filters['email']).'%';
            $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('email', 'like', $term));
        }

        foreach (['from' => '>=', 'to' => '<='] as $key => $operator) {
            $date = $filters[$key] ?? null;

            if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
                $query->whereDate('created_at', $operator, $date);
            }
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Every event name the application can emit (M2). The filter
     * dropdown and the M46 matrix both consume this list.
     *
     * @return string[]
     */
    public static function knownEvents(): array
    {
        return [
            'login',
            'logout',
            'login_failed',
            'login_locked',
            'login_inactive',
            'session_expired',
            'session_revoked',
            'password_reset_requested',
            'password_reset_throttled',
            'password_reset_unknown',
            'password_reset',
            'password_changed',
            'mfa_enrolled',
            'mfa_setup_failed',
            'mfa_verified',
            'mfa_failed',
            'mfa_recovery_used',
            'mfa_codes_regenerated',
            'mfa_disabled',
            'token_issued',
            'token_revoked',
            'user_created',
            'user_updated',
            'role_created',
            'role_updated',
        ];
    }
}

<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * @param  array<string, mixed>  $data  validated admin input
     * @param  string[]  $roleSlugs
     */
    public function createUser(array $data, array $roleSlugs = []): User
    {
        return DB::transaction(function () use ($data, $roleSlugs) {
            $user = User::forceCreate([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => $data['is_active'] ?? true,
                'mfa_enforced' => $data['mfa_enforced'] ?? false,
                'password_changed_at' => now(),
            ]);

            $this->syncRoles($user, $roleSlugs);

            AuditLog::record('user_created', $user, null, [
                'email' => $user->email,
                'roles' => $roleSlugs,
            ]);

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data  validated admin input
     * @param  string[]|null  $roleSlugs  null = leave roles untouched
     */
    public function updateUser(User $actor, User $user, array $data, ?array $roleSlugs = null): User
    {
        if ($actor->is($user)) {
            if (array_key_exists('is_active', $data) && ! $data['is_active']) {
                throw ValidationException::withMessages(['is_active' => 'نمی‌توانید حساب خودتان را غیرفعال کنید.']);
            }

            if ($roleSlugs !== null && $user->hasRole('super-admin') && ! in_array('super-admin', $roleSlugs, true)) {
                throw ValidationException::withMessages(['roles' => 'نمی‌توانید نقش super-admin را از خودتان بگیرید.']);
            }
        }

        $old = [
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'mfa_enforced' => $user->mfa_enforced,
        ];

        return DB::transaction(function () use ($user, $data, $roleSlugs, $old) {
            $user->forceFill(array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'is_active' => $data['is_active'] ?? null,
                'mfa_enforced' => $data['mfa_enforced'] ?? null,
            ], fn ($value) => $value !== null))->save();

            if (array_key_exists('is_active', $data) && ! $data['is_active']) {
                // Deactivation kills every API token immediately.
                $user->tokens()->delete();
            }

            if (isset($data['password']) && is_string($data['password']) && $data['password'] !== '') {
                $user->forceFill([
                    'password' => $data['password'],
                    'password_changed_at' => now(),
                ])->save();

                // A password change kills every API token (stolen-token safety).
                $user->tokens()->delete();
            }

            if ($roleSlugs !== null) {
                $this->syncRoles($user, $roleSlugs);
            }

            AuditLog::record('user_updated', $user, $old, [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'mfa_enforced' => $user->mfa_enforced,
            ]);

            return $user;
        });
    }

    /**
     * @param  string[]  $roleSlugs
     */
    public function syncRoles(User $user, array $roleSlugs): void
    {
        $slugs = [];

        foreach ($roleSlugs as $slug) {
            if (is_string($slug) && $slug !== '') {
                $slugs[] = $slug;
            }
        }

        $ids = Role::whereIn('slug', $slugs)->pluck('id');
        $user->roles()->sync($ids);
    }

    public function paginateUsers(?string $search = null): LengthAwarePaginator
    {
        $query = User::with('roles')->orderByDesc('id');

        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(fn (Builder $builder) => $builder
                ->where('name', 'like', $term)
                ->orWhere('email', 'like', $term));
        }

        return $query->paginate(20)->withQueryString();
    }

    /**
     * @return Collection<int, Role>
     */
    public function allRoles(): Collection
    {
        return Role::orderBy('name')->get();
    }
}

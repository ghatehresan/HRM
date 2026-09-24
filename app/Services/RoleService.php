<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleService
{
    /**
     * @param  array<string, mixed>  $data  validated admin input
     * @param  string[]  $permissionNames
     */
    public function createRole(array $data, array $permissionNames = []): Role
    {
        return DB::transaction(function () use ($data, $permissionNames) {
            $role = Role::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
            ]);

            $this->syncPermissions($role, $permissionNames);

            AuditLog::record('role_created', $role, null, [
                'slug' => $role->slug,
                'permissions' => $permissionNames,
            ]);

            return $role;
        });
    }

    /**
     * @param  array<string, mixed>  $data  validated admin input
     * @param  string[]|null  $permissionNames  null = leave grants untouched
     */
    public function updateRole(Role $role, array $data, ?array $permissionNames = null): Role
    {
        // The super-admin slug is load-bearing (User::hasPermission
        // bypass); renaming it is refused loudly, never ignored.
        if ($role->slug === 'super-admin' && isset($data['slug']) && $data['slug'] !== 'super-admin') {
            throw ValidationException::withMessages(['slug' => 'شناسهٔ نقش super-admin قابل تغییر نیست.']);
        }

        $old = ['name' => $role->name, 'slug' => $role->slug];

        return DB::transaction(function () use ($role, $data, $permissionNames, $old) {
            $role->forceFill(array_filter([
                'name' => $data['name'] ?? null,
                'slug' => $data['slug'] ?? null,
                'description' => $data['description'] ?? null,
            ], fn ($value) => $value !== null))->save();

            if ($permissionNames !== null) {
                $this->syncPermissions($role, $permissionNames);
            }

            AuditLog::record('role_updated', $role, $old, [
                'name' => $role->name,
                'slug' => $role->slug,
            ]);

            return $role;
        });
    }

    /**
     * @param  string[]  $permissionNames
     */
    public function syncPermissions(Role $role, array $permissionNames): void
    {
        $names = [];

        foreach ($permissionNames as $name) {
            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }

        $ids = Permission::whereIn('name', $names)->pluck('id');
        $role->permissions()->sync($ids);
    }

    public function paginateRoles(): LengthAwarePaginator
    {
        return Role::withCount(['users', 'permissions'])->orderBy('name')->paginate(20);
    }

    /**
     * All permissions grouped by their display group for the role form.
     *
     * @return Collection<string, Collection<int, Permission>>
     */
    public function groupedPermissions(): Collection
    {
        return Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');
    }
}

<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Seeds the M2 permission subset (the full vocabulary lives in
     * AUTHORIZATION.md §2; each milestone extends this seeder).
     * Additive by design: syncWithoutDetaching never strips grants an
     * admin customized by hand.
     */
    public function run(): void
    {
        $permissions = [
            ['users.view', 'user', 'مشاهدهٔ فهرست کاربران'],
            ['users.manage', 'user', 'ساخت و ویرایش کاربران و نقش‌هایشان'],
            ['roles.view', 'role', 'مشاهدهٔ نقش‌ها'],
            ['roles.manage', 'role', 'ساخت و ویرایش نقش‌ها و مجوزها'],
            ['audit.view', 'audit', 'مشاهدهٔ لاگ حسابرسی'],
        ];

        foreach ($permissions as [$name, $group, $description]) {
            Permission::firstOrCreate(['name' => $name], [
                'group' => $group,
                'description' => $description,
            ]);
        }

        // super-admin needs no rows: User::hasPermission bypasses for it.
        $map = [
            'hr-admin' => ['users.view', 'users.manage', 'roles.view', 'roles.manage', 'audit.view'],
            'hr-manager' => ['users.view', 'audit.view'],
            'auditor' => ['users.view', 'audit.view'],
        ];

        foreach ($map as $slug => $names) {
            $role = Role::where('slug', $slug)->first();

            if ($role === null) {
                continue;
            }

            $ids = Permission::whereIn('name', $names)->pluck('id');
            $role->permissions()->syncWithoutDetaching($ids);
        }
    }
}

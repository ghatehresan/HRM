<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

final class RoleManagementTest extends TestCase
{
    public function test_index_lists_roles(): void
    {
        $admin = $this->userWithPermissions(['roles.view']);
        Role::factory()->create(['slug' => 'auditor', 'name' => 'Auditor']);

        $this->actingAs($admin)->get(route('admin.roles.index'))
            ->assertOk()->assertSee('Auditor');
    }

    public function test_admin_can_create_role_with_permissions(): void
    {
        Permission::firstOrCreate(['name' => 'users.view'], ['group' => 'users']);
        Permission::firstOrCreate(['name' => 'users.manage'], ['group' => 'users']);
        $admin = $this->userWithPermissions(['roles.manage']);

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'مدیر منابع انسانی',
            'slug' => 'hr-manager',
            'permissions' => ['users.view', 'users.manage'],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::where('slug', 'hr-manager')->firstOrFail();
        $this->assertSame(2, $role->permissions()->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'role_created']);
    }

    public function test_super_admin_slug_is_protected(): void
    {
        $admin = $this->userWithPermissions(['roles.manage']);

        $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Fake',
            'slug' => 'super-admin',
            'permissions' => [],
        ])->assertSessionHasErrors('slug');

        $this->assertNull(Role::where('slug', 'super-admin')->first());
    }

    public function test_admin_can_update_role_and_sync_permissions(): void
    {
        Permission::firstOrCreate(['name' => 'users.view'], ['group' => 'users']);
        Permission::firstOrCreate(['name' => 'audit.view'], ['group' => 'audit']);
        $role = Role::factory()->create(['slug' => 'auditor']);
        $admin = $this->userWithPermissions(['roles.manage']);

        $this->actingAs($admin)->put(route('admin.roles.update', $role), [
            'name' => 'Auditor+',
            'slug' => 'auditor',
            'permissions' => ['audit.view'],
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertSame(['audit.view'], $role->refresh()->permissions()->pluck('name')->all());
        $this->assertDatabaseHas('audit_logs', ['event' => 'role_updated']);
    }

    public function test_role_pages_deny_users_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
    }
}

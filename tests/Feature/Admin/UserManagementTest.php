<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

final class UserManagementTest extends TestCase
{
    private const STRONG = 'X7#mQ9!vL2$pR5&kD8@nF4';

    public function test_index_lists_and_searches_users(): void
    {
        $admin = $this->userWithPermissions(['users.view']);
        User::factory()->create(['name' => 'Sara Ahmadi', 'email' => 'sara.ahmadi@example.com']);

        $this->actingAs($admin);
        $this->get(route('admin.users.index'))->assertOk()->assertSee('Sara Ahmadi');
        $this->get(route('admin.users.index', ['q' => 'sara.ahmadi@example.com']))
            ->assertOk()->assertSee('Sara Ahmadi');
        $this->get(route('admin.users.index', ['q' => 'no-such-person']))
            ->assertOk()->assertDontSee('Sara Ahmadi');
    }

    public function test_admin_can_create_user_with_roles(): void
    {
        Role::factory()->create(['slug' => 'employee', 'name' => 'Employee']);
        $admin = $this->userWithPermissions(['users.manage']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'کاربر تازه',
            'email' => 'fresh@example.com',
            'password' => self::STRONG,
            'roles' => ['employee'],
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $created = User::where('email', 'fresh@example.com')->firstOrFail();
        $this->assertTrue($created->hasRole('employee'));
        $this->assertDatabaseHas('audit_logs', ['event' => 'user_created']);
    }

    public function test_create_validates_password_and_roles(): void
    {
        $admin = $this->userWithPermissions(['users.manage']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'X',
            'email' => 'bad@example.com',
            'password' => 'passwordpassword',
            'roles' => ['ghost-role'],
        ])->assertSessionHasErrors(['password', 'roles.0']);
    }

    public function test_admin_can_update_user(): void
    {
        Role::factory()->create(['slug' => 'employee']);
        Role::factory()->create(['slug' => 'hr-viewer']);
        $admin = $this->userWithPermissions(['users.manage']);
        $target = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => 'نام ویرایش‌شده',
            'email' => $target->email,
            'roles' => ['employee', 'hr-viewer'],
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $target->refresh();
        $this->assertSame('نام ویرایش‌شده', $target->name);
        $this->assertTrue($target->hasRole('employee'));
        $this->assertTrue($target->hasRole('hr-viewer'));
        $this->assertDatabaseHas('audit_logs', ['event' => 'user_updated']);
    }

    public function test_admin_password_reset_revokes_tokens(): void
    {
        $admin = $this->userWithPermissions(['users.manage']);
        $target = User::factory()->create();
        $target->createToken('device');

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'password' => self::STRONG,
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame(0, $target->tokens()->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'user_updated']);
    }

    public function test_admin_cannot_deactivate_self_or_strip_own_super_admin(): void
    {
        $superAdmin = Role::factory()->create(['slug' => 'super-admin']);
        Role::factory()->create(['slug' => 'employee']);
        $admin = $this->userWithPermissions(['users.manage']);
        $admin->roles()->attach($superAdmin);

        // Self-deactivation is refused.
        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'is_active' => '0',
        ])->assertSessionHasErrors('is_active');
        $this->assertTrue($admin->refresh()->is_active);

        // Stripping the last super-admin role from self is refused.
        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'is_active' => '1',
            'roles' => ['employee'],
        ])->assertSessionHasErrors('roles');
        $this->assertTrue($admin->refresh()->hasRole('super-admin'));
    }
}

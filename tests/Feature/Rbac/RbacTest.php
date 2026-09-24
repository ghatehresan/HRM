<?php

declare(strict_types=1);

namespace Tests\Feature\Rbac;

use App\Models\User;
use Tests\TestCase;

final class RbacTest extends TestCase
{
    public function test_super_admin_bypasses_all_permissions(): void
    {
        $user = $this->userWithRoles(['super-admin']);

        $this->assertTrue($user->hasPermission('anything.at.all'));
        $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
    }

    public function test_user_with_permission_can_access_admin_page(): void
    {
        $user = $this->userWithPermissions(['users.view']);

        $this->actingAs($user);
        $this->get(route('admin.users.index'))->assertOk();
        // View does not imply manage.
        $this->get(route('admin.users.create'))->assertForbidden();
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.roles.index'))->assertForbidden();
        $this->get(route('admin.audit-logs.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_policies_match_middleware(): void
    {
        $allowed = $this->userWithPermissions(['users.manage']);
        $denied = User::factory()->create();
        $target = User::factory()->create();

        $this->assertTrue($allowed->can('update', $target));
        $this->assertFalse($denied->can('update', $target));
        $this->assertFalse($allowed->can('delete', $target));
        $this->assertFalse($denied->can('delete', $target));
    }

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $user = $this->userWithPermissions(['users.view']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));

        $user->forceFill(['is_active' => false])->save();

        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['event' => 'session_revoked']);
    }

    public function test_absolute_session_timeout_logs_out(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));

        $this->travel(13)->hours();

        $this->get(route('password.change.edit'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['event' => 'session_expired']);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use Tests\TestCase;

/**
 * M2 release gate: the M46 access-matrix rows that apply before employee
 * data exists. Employee self/other/department scope rows are deferred to
 * the milestones that introduce those models (M4+).
 */
final class AccessMatrixTest extends TestCase
{
    /**
     * M46: «کاربر غیرفعال نمی‌تواند وارد شود».
     */
    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * M46: «مسیرهای /api/v1/* بدون توکن 401 می‌دهند».
     */
    public function test_api_routes_reject_unauthenticated_calls(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
        $this->getJson('/api/v1/tokens')->assertUnauthorized();
        $this->deleteJson('/api/v1/tokens/1')->assertUnauthorized();
    }

    /**
     * M46: «Audit Log قابل حذف/ویرایش از هیچ مسیری نیست».
     */
    public function test_audit_log_has_no_mutation_surface(): void
    {
        $this->put('/admin/audit-logs/1')->assertStatus(405);
        $this->delete('/admin/audit-logs/1')->assertStatus(405);
    }

    /**
     * M46: «کاربر بدون permission صفحهٔ admin را نمی‌بیند».
     */
    public function test_admin_pages_deny_users_without_permissions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.roles.index'))->assertForbidden();
        $this->get(route('admin.audit-logs.index'))->assertForbidden();
    }
}

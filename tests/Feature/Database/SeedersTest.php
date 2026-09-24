<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\DevAdminSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use RuntimeException;
use Tests\TestCase;

final class SeedersTest extends TestCase
{
    public function test_identity_seeders_are_idempotent(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->assertSame(8, Role::count());
        $this->assertSame(5, Permission::count());
        $this->assertSame(5, Role::where('slug', 'hr-admin')->firstOrFail()->permissions()->count());
    }

    public function test_dev_seeder_refuses_non_local_environments(): void
    {
        // PHPUnit runs with APP_ENV=testing, so this must throw.
        $this->expectException(RuntimeException::class);
        $this->seed(DevAdminSeeder::class);
    }
}

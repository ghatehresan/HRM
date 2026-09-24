<?php

namespace Tests;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Views are rendered without compiled Vite assets in tests.
        $this->withoutVite();
    }

    /**
     * @param  string[]  $permissions
     * @param  array<string, mixed>  $attributes
     */
    protected function userWithPermissions(array $permissions, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $role = Role::factory()->create();

        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(
                ['name' => $name],
                ['group' => explode('.', $name)[0] ?? 'misc', 'description' => null],
            );
            $role->permissions()->attach($permission);
        }

        $user->roles()->attach($role);

        return $user;
    }

    /**
     * @param  string[]  $slugs
     * @param  array<string, mixed>  $attributes
     */
    protected function userWithRoles(array $slugs, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);

        foreach ($slugs as $slug) {
            $role = Role::firstOrCreate(['slug' => $slug], ['name' => $slug]);
            $user->roles()->attach($role);
        }

        return $user;
    }
}

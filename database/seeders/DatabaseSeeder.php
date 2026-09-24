<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Base data (roles/permissions) seeds everywhere; demo accounts
     * only on local. Seeders write through models directly (no
     * services), so seeding never pollutes the audit trail.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call(DevAdminSeeder::class);
        }
    }
}

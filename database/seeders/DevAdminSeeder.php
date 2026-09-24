<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * LOCAL-ONLY demo accounts (fake data). Refuses to run outside the
 * local environment; production gets roles/permissions only.
 */
class DevAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal()) {
            throw new RuntimeException('DevAdminSeeder runs in the local environment only.');
        }

        $email = (string) env('ADMIN_EMAIL', 'admin@example.com');
        $password = (string) env('ADMIN_PASSWORD', '');

        if ($password === '') {
            $password = 'Dev-'.Str::upper(Str::random(4)).'-'.Str::random(4).'-1405';

            if ($this->command instanceof Command) {
                $this->command->warn("ADMIN_PASSWORD not set — generated dev password: {$password}");
            }
        }

        $this->makeUser('مدیر سیستم', $email, $password, ['super-admin']);

        $faker = FakerFactory::create('fa_IR');

        $this->makeUser($faker->name(), 'hr@example.com', $password, ['hr-admin']);
        $this->makeUser($faker->name(), 'employee@example.com', $password, ['employee']);

        for ($i = 0; $i < 3; $i++) {
            $this->makeUser($faker->name(), $faker->unique()->safeEmail(), $password, ['employee']);
        }
    }

    /**
     * @param  string[]  $roleSlugs
     */
    private function makeUser(string $name, string $email, string $password, array $roleSlugs): User
    {
        $user = User::firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => $password,
            'password_changed_at' => now(),
        ]);

        $ids = Role::whereIn('slug', $roleSlugs)->pluck('id');
        $user->roles()->syncWithoutDetaching($ids);

        return $user;
    }
}

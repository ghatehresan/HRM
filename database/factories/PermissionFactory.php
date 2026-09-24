<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $group = fake()->word();

        return [
            'name' => $group.'.'.fake()->unique()->word(),
            'group' => $group,
            'description' => fake()->sentence(),
        ];
    }
}

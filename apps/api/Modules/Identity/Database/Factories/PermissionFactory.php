<?php

namespace Modules\Identity\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Models\Permission;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $module = fake()->randomElement(['employees', 'products', 'stock', 'orders', 'customers']);
        $action = fake()->randomElement(['view', 'manage']);

        return [
            'name' => "{$module}.{$action}",
            'module' => $module,
            'description' => null,
        ];
    }
}

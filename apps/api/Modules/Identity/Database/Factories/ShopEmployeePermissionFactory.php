<?php

namespace Modules\Identity\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Models\Permission;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\ShopEmployeePermission;

class ShopEmployeePermissionFactory extends Factory
{
    protected $model = ShopEmployeePermission::class;

    public function definition(): array
    {
        return [
            'shop_employee_id' => ShopEmployee::factory(),
            'permission_id' => Permission::factory(),
            'is_granted' => true,
        ];
    }

    public function denied(): static
    {
        return $this->state(fn () => ['is_granted' => false]);
    }
}

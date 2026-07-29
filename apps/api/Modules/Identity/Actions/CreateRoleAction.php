<?php

namespace Modules\Identity\Actions;

use Modules\Identity\Models\Role;

class CreateRoleAction
{
    /**
     * @param array{name: string, description?: string, permissions?: array<string>} $data
     */
    public function execute(int $shopId, array $data): Role
    {
        $role = Role::create([
            'shop_id' => $shopId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_system' => false,
        ]);

        if (!empty($data['permissions'])) {
            $role->permissions()->sync($data['permissions']);
        }

        return $role->load('permissions');
    }
}
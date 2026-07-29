<?php

namespace Modules\Identity\Actions;

use Modules\Identity\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UpdateRoleAction
{
    /**
     * @param array{name?: string, description?: string, permissions?: array<string>} $data
     */
    public function execute(Role $role, array $data): Role
    {
        // Protection : Le rôle Owner / système ne peut pas avoir ses permissions de base modifiées arbitrairement
        if ($role->slug === 'owner' || $role->is_system) {
            // On autorise la modification de la description, mais pas du nom/slug système
            unset($data['name']);
        }

        $role->update(array_filter([
            'name' => $data['name'] ?? $role->name,
            'description' => $data['description'] ?? $role->description,
        ]));

        if (isset($data['permissions']) && $role->slug !== 'owner') {
            $role->permissions()->sync($data['permissions']);
        }

        return $role->fresh('permissions');
    }
}
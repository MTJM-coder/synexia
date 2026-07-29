<?php

namespace Modules\Identity\Actions;

use Modules\Identity\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SyncRolePermissionsAction
{
    /**
     * @param array<int, string> $permissionIds
     */
    public function execute(Role $role, array $permissionIds): Role
    {
        if ($role->slug === 'owner') {
            throw new HttpException(403, "Les permissions du propriétaire ne peuvent pas être altérées.");
        }

        $role->permissions()->sync($permissionIds);

        return $role->fresh('permissions');
    }
}
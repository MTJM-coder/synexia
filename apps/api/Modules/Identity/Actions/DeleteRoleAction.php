<?php

namespace Modules\Identity\Actions;

use Illuminate\Validation\ValidationException;
use Modules\Identity\Models\Role;

class DeleteRoleAction
{
    public function execute(Role $role): bool
    {
        // 1. Bloquer la suppression si is_system est vrai ou slug == 'owner'
        if ($role->is_system || $role->slug === 'owner') {
            abort(403, 'Impossible de supprimer un rôle système.');
        }

        // 2. Bloquer si la relation shopEmployees contient des données
        if ($role->shopEmployees()->exists()) {
            throw ValidationException::withMessages([
                'role' => ['Impossible de supprimer un rôle assigné à des employés.'],
            ]);
        }

        return (bool) $role->delete();
    }
}
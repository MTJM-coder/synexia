<?php

namespace Modules\Identity\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Events\EmployeePermissionOverridden;
use Modules\Identity\Events\EmployeeReactivated;
use Modules\Identity\Events\EmployeeRemoved;
use Modules\Identity\Events\EmployeeRoleAssigned;
use Modules\Identity\Events\EmployeeSuspended;

class LogPermissionChange
{
    public function handleRoleAssigned(EmployeeRoleAssigned $event): void
    {
        DB::table('activity_logs')->insert([
            'shop_id' => $event->employee->shop_id,
            'user_id' => $event->changedByUserId,
            'action' => 'shop_employee.role_assigned',
            'subject_type' => 'ShopEmployee',
            'subject_id' => $event->employee->id,
            'description' => "Rôle changé : {$event->previousRole?->name} → {$event->newRole->name}",
            'old_values' => json_encode(['role_id' => $event->previousRole?->id]),
            'new_values' => json_encode(['role_id' => $event->newRole->id]),
            'created_at' => now(),
        ]);
    }

    public function handlePermissionOverridden(EmployeePermissionOverridden $event): void
    {
        $action = match ($event->isGranted) {
            true => 'shop_employee.permission_granted',
            false => 'shop_employee.permission_denied',
            null => 'shop_employee.permission_override_cleared',
        };

        DB::table('activity_logs')->insert([
            'shop_id' => $event->employee->shop_id,
            'user_id' => $event->changedByUserId,
            'action' => $action,
            'subject_type' => 'ShopEmployee',
            'subject_id' => $event->employee->id,
            'description' => "Permission « {$event->permission->name} » : {$action}",
            'old_values' => json_encode(['is_granted' => $event->previousIsGranted]),
            'new_values' => json_encode(['permission_id' => $event->permission->id, 'is_granted' => $event->isGranted]),
            'created_at' => now(),
        ]);
    }

    public function handleSuspended(EmployeeSuspended $event): void
    {
        DB::table('activity_logs')->insert([
            'shop_id' => $event->employee->shop_id,
            'user_id' => $event->changedByUserId,
            'action' => 'shop_employee.suspended',
            'subject_type' => 'ShopEmployee',
            'subject_id' => $event->employee->id,
            'description' => 'Employé suspendu.',
            'created_at' => now(),
        ]);
    }

    public function handleReactivated(EmployeeReactivated $event): void
    {
        DB::table('activity_logs')->insert([
            'shop_id' => $event->employee->shop_id,
            'user_id' => $event->changedByUserId,
            'action' => 'shop_employee.reactivated',
            'subject_type' => 'ShopEmployee',
            'subject_id' => $event->employee->id,
            'description' => 'Employé réactivé.',
            'created_at' => now(),
        ]);
    }

    public function handleRemoved(EmployeeRemoved $event): void
    {
        DB::table('activity_logs')->insert([
            'shop_id' => $event->employee->shop_id,
            'user_id' => $event->changedByUserId,
            'action' => 'shop_employee.removed',
            'subject_type' => 'ShopEmployee',
            'subject_id' => $event->employee->id,
            'description' => 'Employé retiré de la boutique.',
            'created_at' => now(),
        ]);
    }
}

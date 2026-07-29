<?php

namespace Modules\Identity\Listeners;

use Modules\Identity\Contracts\PermissionResolverContract;
use Modules\Identity\Events\EmployeePermissionOverridden;
use Modules\Identity\Events\EmployeeReactivated;
use Modules\Identity\Events\EmployeeRemoved;
use Modules\Identity\Events\EmployeeRoleAssigned;
use Modules\Identity\Events\EmployeeSuspended;

class InvalidatePermissionCache
{
    public function __construct(
        private readonly PermissionResolverContract $resolver,
    ) {
    }

    public function handleRoleAssigned(EmployeeRoleAssigned $event): void
    {
        $this->resolver->forgetForEmployee($event->employee);
    }

    public function handlePermissionOverridden(EmployeePermissionOverridden $event): void
    {
        $this->resolver->forgetForEmployee($event->employee);
    }

    public function handleSuspended(EmployeeSuspended $event): void
    {
        $this->resolver->forgetForEmployee($event->employee);
    }

    public function handleReactivated(EmployeeReactivated $event): void
    {
        $this->resolver->forgetForEmployee($event->employee);
    }

    public function handleRemoved(EmployeeRemoved $event): void
    {
        $this->resolver->forgetForEmployee($event->employee);
    }
}

<?php

namespace Modules\Identity\Listeners;

use Modules\Identity\Contracts\PermissionResolverContract;
use Modules\Identity\Events\EmployeePermissionOverridden;
use Modules\Identity\Events\EmployeeRoleAssigned;

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
}

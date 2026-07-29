<?php

namespace Modules\Identity\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Identity\Contracts\PermissionResolverContract;
use Modules\Identity\Domain\ValueObjects\PermissionSet;
use Modules\Identity\Models\ShopEmployee;

class PermissionResolver implements PermissionResolverContract
{
    private const CACHE_TTL_SECONDS = 3600;

    public function resolveForEmployee(ShopEmployee $employee): PermissionSet
    {
        // Un employé non actif (suspendu...) n'a aucun droit, quel que soit
        // son rôle — vérifié explicitement, pas seulement supposé.
        if (! $employee->isActive()) {
            return PermissionSet::empty();
        }

        return Cache::remember(
            $this->cacheKey($employee),
            self::CACHE_TTL_SECONDS,
            fn () => $this->resolveFresh($employee),
        );
    }

    public function forgetForEmployee(ShopEmployee $employee): void
    {
        Cache::forget($this->cacheKey($employee));
    }

    private function resolveFresh(ShopEmployee $employee): PermissionSet
    {
        $rolePermissions = $employee->role
            ? $employee->role->permissions()->pluck('name')->all()
            : [];

        $overrides = $employee->permissionOverrides()->with('permission')->get();

        $granted = $overrides->where('is_granted', true)->pluck('permission.name')->all();
        $denied = $overrides->where('is_granted', false)->pluck('permission.name')->all();

        $effective = array_diff(
            array_unique(array_merge($rolePermissions, $granted)),
            $denied,
        );

        return new PermissionSet(array_values($effective));
    }

    private function cacheKey(ShopEmployee $employee): string
    {
        return "identity:permissions:employee:{$employee->id}";
    }
}

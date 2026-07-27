<?php

namespace Modules\Identity\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Modules\Identity\Contracts\PermissionResolverContract;
use Modules\Identity\Domain\ValueObjects\PermissionSet;
use Modules\Identity\Models\ShopEmployee;

class PermissionResolver implements PermissionResolverContract
{
    private const CACHE_TTL_SECONDS = 3600;

    public function __construct(
        private readonly CacheRepository $cache,
    ) {
    }

    public function resolveForEmployee(ShopEmployee $employee): PermissionSet
    {
        return $this->cache->remember(
            $this->cacheKey($employee),
            self::CACHE_TTL_SECONDS,
            fn () => $this->resolveUncached($employee),
        );
    }

    public function forgetForEmployee(ShopEmployee $employee): void
    {
        $this->cache->forget($this->cacheKey($employee));
    }

    /**
     * La logique métier réelle : role_permissions + surcharges grant - surcharges deny.
     * Un employé suspendu n'a JAMAIS aucune permission, quel que soit son rôle.
     */
    private function resolveUncached(ShopEmployee $employee): PermissionSet
    {
        if (! $employee->isActive()) {
            return PermissionSet::empty();
        }

        $rolePermissions = $employee->role
            ->permissions()
            ->pluck('name')
            ->all();

        $overrides = $employee->permissionOverrides()
            ->with('permission:id,name')
            ->get();

        $granted = $overrides
            ->where('is_granted', true)
            ->pluck('permission.name')
            ->all();

        $denied = $overrides
            ->where('is_granted', false)
            ->pluck('permission.name')
            ->all();

        $effective = array_diff(
            array_unique(array_merge($rolePermissions, $granted)),
            $denied,
        );

        return new PermissionSet($effective);
    }

    private function cacheKey(ShopEmployee $employee): string
    {
        return "identity:permissions:shop_employee:{$employee->id}";
    }
}

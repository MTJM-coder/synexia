<?php

namespace Modules\Catalog\Policies;

use Modules\Identity\Contracts\PermissionResolverContract;
use Modules\Identity\Models\User;

/**
 * Contrairement à ShopPolicy (Marketplace, propriétaire uniquement), la
 * gestion du catalogue est déléguable à un employé — on réutilise donc le
 * système de permissions d'Identity (permissions "products.view" et
 * "products.manage", déjà présentes dans RolesAndPermissionsSeeder) plutôt
 * qu'un simple check owner_id.
 */
class ProductPolicy
{
    public function __construct(
        private readonly PermissionResolverContract $resolver,
    ) {
    }

    public function viewAny(User $user, int $shopId): bool
    {
        return $this->employeeCan($user, $shopId, 'products.view')
            || $this->employeeCan($user, $shopId, 'products.manage');
    }

    public function manage(User $user, int $shopId): bool
    {
        return $this->employeeCan($user, $shopId, 'products.manage');
    }

    private function employeeCan(User $user, int $shopId, string $permission): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        $employee = $user->shopEmployees()->where('shop_id', $shopId)->first();

        if ($employee === null) {
            return false;
        }

        return $this->resolver->resolveForEmployee($employee)->has($permission);
    }
}

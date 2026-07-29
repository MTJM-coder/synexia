<?php

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Contracts\PlanLimitCheckerContract;
use Modules\Marketplace\Domain\ValueObjects\PlanLimits;
use Modules\Marketplace\Exceptions\PlanLimitExceededException;
use Modules\Marketplace\Models\Shop;

class PlanLimitChecker implements PlanLimitCheckerContract
{
    public function currentLimits(Shop $shop): PlanLimits
    {
        $plan = $shop->subscriptionPlan;

        // Pas de plan (boutique en attente de validation, par ex.) = aucune limite
        // n'est appliquée par ce service ; à ce stade la boutique n'est de toute
        // façon pas encore "active" pour créer quoi que ce soit.
        if ($plan === null) {
            return new PlanLimits(maxProducts: 0, maxEmployees: 0, maxWarehouses: 0);
        }

        return $plan->toLimits();
    }

    public function assertCanAddProduct(Shop $shop, int $currentProductCount): void
    {
        $limits = $this->currentLimits($shop);

        if (! $limits->allowsProduct($currentProductCount)) {
            throw new PlanLimitExceededException($shop, 'produits', $limits->maxProducts);
        }
    }

    public function assertCanAddEmployee(Shop $shop, int $currentEmployeeCount): void
    {
        $limits = $this->currentLimits($shop);

        if (! $limits->allowsEmployee($currentEmployeeCount)) {
            throw new PlanLimitExceededException($shop, 'employés', $limits->maxEmployees);
        }
    }

    public function assertCanAddWarehouse(Shop $shop, int $currentWarehouseCount): void
    {
        $limits = $this->currentLimits($shop);

        if (! $limits->allowsWarehouse($currentWarehouseCount)) {
            throw new PlanLimitExceededException($shop, 'entrepôts', $limits->maxWarehouses);
        }
    }
}

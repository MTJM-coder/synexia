<?php

namespace Modules\Marketplace\Contracts;

use Modules\Marketplace\Domain\ValueObjects\PlanLimits;
use Modules\Marketplace\Models\Shop;

/**
 * Frontière du module Marketplace pour tout ce qui touche aux limites de
 * plan. Catalog (avant de créer un produit), Identity (avant de créer un
 * employé), Inventory (avant de créer un entrepôt) dépendent de ce contrat
 * — jamais de SubscriptionPlan ou ShopSubscription directement.
 */
interface PlanLimitCheckerContract
{
    public function currentLimits(Shop $shop): PlanLimits;

    /**
     * @throws \Modules\Marketplace\Exceptions\PlanLimitExceededException
     */
    public function assertCanAddProduct(Shop $shop, int $currentProductCount): void;

    /**
     * @throws \Modules\Marketplace\Exceptions\PlanLimitExceededException
     */
    public function assertCanAddEmployee(Shop $shop, int $currentEmployeeCount): void;

    /**
     * @throws \Modules\Marketplace\Exceptions\PlanLimitExceededException
     */
    public function assertCanAddWarehouse(Shop $shop, int $currentWarehouseCount): void;
}

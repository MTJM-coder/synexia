<?php

namespace Modules\Marketplace\Domain\ValueObjects;

/**
 * Limites d'un plan d'abonnement. `null` = illimité, jamais 0 (0 voudrait
 * dire "aucun produit autorisé", ce qui n'a pas de sens métier — cette
 * ambiguïté ne doit pas exister ailleurs que dans cette classe).
 */
final class PlanLimits
{
    public function __construct(
        public readonly ?int $maxProducts,
        public readonly ?int $maxEmployees,
        public readonly ?int $maxWarehouses,
    ) {
    }

    public function allowsProduct(int $currentCount): bool
    {
        return $this->maxProducts === null || $currentCount < $this->maxProducts;
    }

    public function allowsEmployee(int $currentCount): bool
    {
        return $this->maxEmployees === null || $currentCount < $this->maxEmployees;
    }

    public function allowsWarehouse(int $currentCount): bool
    {
        return $this->maxWarehouses === null || $currentCount < $this->maxWarehouses;
    }
}

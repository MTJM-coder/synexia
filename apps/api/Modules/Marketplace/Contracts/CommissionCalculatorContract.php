<?php

namespace Modules\Marketplace\Contracts;

use Modules\Marketplace\Domain\ValueObjects\CommissionRate;
use Modules\Marketplace\Models\Shop;

/**
 * Frontière du module Marketplace pour le calcul de commission. Sales (au
 * moment de finaliser une commande) et Accounting (pour les rapports)
 * dépendent de ce contrat — jamais de CommissionRule directement, car
 * l'ordre de priorité des règles ne doit exister qu'à un seul endroit.
 */
interface CommissionCalculatorContract
{
    /**
     * Résout le taux applicable selon la priorité :
     * règle boutique+catégorie > règle boutique seule > règle catégorie globale > taux du plan.
     */
    public function rateFor(Shop $shop, ?int $categoryId = null): CommissionRate;

    public function calculateAmount(Shop $shop, float $orderAmount, ?int $categoryId = null): float;
}

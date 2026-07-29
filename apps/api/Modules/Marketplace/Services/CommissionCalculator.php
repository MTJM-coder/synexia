<?php

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Contracts\CommissionCalculatorContract;
use Modules\Marketplace\Domain\ValueObjects\CommissionRate;
use Modules\Marketplace\Models\CommissionRule;
use Modules\Marketplace\Models\Shop;

class CommissionCalculator implements CommissionCalculatorContract
{
    public function rateFor(Shop $shop, ?int $categoryId = null): CommissionRate
    {
        // 1. Règle spécifique à CETTE boutique ET cette catégorie.
        if ($categoryId !== null) {
            $specific = $this->findRule($shop->id, $categoryId);
            if ($specific !== null) {
                return new CommissionRate((float) $specific->rate);
            }
        }

        // 2. Règle spécifique à cette boutique, toutes catégories.
        $shopWide = $this->findRule($shop->id, null);
        if ($shopWide !== null) {
            return new CommissionRate((float) $shopWide->rate);
        }

        // 3. Règle globale pour cette catégorie (toutes boutiques).
        if ($categoryId !== null) {
            $categoryWide = $this->findRule(null, $categoryId);
            if ($categoryWide !== null) {
                return new CommissionRate((float) $categoryWide->rate);
            }
        }

        // 4. Repli : taux par défaut du plan d'abonnement de la boutique.
        $planRate = $shop->subscriptionPlan?->commission_rate ?? 0;

        return new CommissionRate((float) $planRate);
    }

    public function calculateAmount(Shop $shop, float $orderAmount, ?int $categoryId = null): float
    {
        return $this->rateFor($shop, $categoryId)->calculateAmount($orderAmount);
    }

    private function findRule(?int $shopId, ?int $categoryId): ?CommissionRule
    {
        return CommissionRule::query()
            ->where('shop_id', $shopId)
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->first();
    }
}

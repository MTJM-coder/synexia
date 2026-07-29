<?php

namespace Modules\Marketplace\Domain\ValueObjects;

/**
 * Un taux de commission résolu (déjà tranché entre règle spécifique boutique,
 * règle catégorie, ou taux par défaut du plan — voir CommissionCalculator).
 * Volontairement immuable : un montant de commission déjà calculé pour une
 * commande ne doit jamais changer rétroactivement si le taux change ensuite.
 */
final class CommissionRate
{
    public function __construct(
        public readonly float $percentage,
    ) {
        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException("Un taux de commission doit être compris entre 0 et 100, reçu : {$percentage}");
        }
    }

    public function calculateAmount(float $baseAmount): float
    {
        return round($baseAmount * ($this->percentage / 100), 2);
    }
}

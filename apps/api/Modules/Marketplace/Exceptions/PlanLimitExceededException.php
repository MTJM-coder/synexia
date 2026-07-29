<?php

namespace Modules\Marketplace\Exceptions;

use Modules\Marketplace\Models\Shop;

/**
 * Levée quand une boutique tente de dépasser une limite de son plan
 * d'abonnement (produits, employés, entrepôts). Catalog/Identity/Inventory
 * doivent pouvoir l'attraper spécifiquement pour proposer une mise à niveau
 * de plan plutôt que de laisser remonter une erreur générique.
 */
class PlanLimitExceededException extends \RuntimeException
{
    public function __construct(
        public readonly Shop $shop,
        public readonly string $resource,
        public readonly int $limit,
    ) {
        parent::__construct(sprintf(
            'La boutique "%s" a atteint la limite de %d %s autorisée par son plan actuel.',
            $shop->name,
            $limit,
            $resource,
        ));
    }
}

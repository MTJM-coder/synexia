<?php

namespace Modules\Marketplace\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * NOTE IMPORTANTE : contrairement à ShopResource (qui expose ->uuid comme
 * "id" public), ce resource expose l'id interne BIGINT tel quel, sous le
 * nom subscription_plan_id — car CreateShopRequest / SubscribeToPlanRequest
 * valident et consomment cet id interne directement
 * (exists:subscription_plans,id). C'est une entorse consciente au principe
 * "jamais l'id interne dans l'API" du schéma d'origine, pas un oubli.
 * Deux façons de la fermer si vous le souhaitez :
 *   1. Ajouter une colonne uuid à subscription_plans et faire valider/
 *      chercher les Requests dessus (cohérent avec le reste du schéma) ;
 *   2. Accepter cette exception pour cette table précise (les plans sont peu
 *      nombreux, publics, non sensibles — le risque d'exposer leur id est
 *      faible comparé à exposer un id de commande ou d'utilisateur).
 * Je n'ai pas tranché à votre place, voir la conversation.
 */
class SubscriptionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'subscription_plan_id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => $this->price,
            'currency' => $this->currency,
            'billing_period' => $this->billing_period,
            'max_products' => $this->max_products,
            'max_employees' => $this->max_employees,
            'max_warehouses' => $this->max_warehouses,
            'commission_rate' => $this->commission_rate,
            'features' => $this->features,
        ];
    }
}

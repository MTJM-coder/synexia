<?php

namespace Modules\Marketplace\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'currency' => $this->currency,
            'language' => $this->language,
            'timezone' => $this->timezone,
            'tax_rate' => (float) $this->tax_rate,
            'tax_inclusive' => $this->tax_inclusive,
            'opening_hours' => $this->opening_hours,
            'allow_pickup' => $this->allow_pickup,
            'allow_delivery' => $this->allow_delivery,
            'delivery_radius_km' => $this->delivery_radius_km !== null ? (float) $this->delivery_radius_km : null,
        ];
    }
}

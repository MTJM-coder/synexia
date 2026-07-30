<?php

namespace Modules\Marketplace\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'slogan' => $this->slogan,
            'description' => $this->description,
            'email' => $this->email,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'website' => $this->website,
            'social_links' => $this->social_links,
            'address_line' => $this->address_line,
            'city' => $this->city,
            'region' => $this->region,
            'country' => $this->country,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'subscription_plan' => $this->whenLoaded('subscriptionPlan', fn () => [
                'id' => $this->subscriptionPlan->id,
                'name' => $this->subscriptionPlan->name,
                'commission_rate' => (float) $this->subscriptionPlan->commission_rate,
            ]),
            'settings' => $this->whenLoaded('settings', fn () => [
                'currency' => $this->settings->currency,
                'timezone' => $this->settings->timezone,
                'tax_rate' => (float) $this->settings->tax_rate,
                'allow_pickup' => $this->settings->allow_pickup,
                'allow_delivery' => $this->settings->allow_delivery,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

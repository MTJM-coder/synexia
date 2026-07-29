<?php

namespace Modules\Marketplace\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Marketplace\Models\Shop;
use Modules\Marketplace\Models\ShopSetting;

class ShopSettingFactory extends Factory
{
    protected $model = ShopSetting::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'currency' => 'XAF',
            'language' => 'fr',
            'timezone' => 'Africa/Douala',
            'tax_rate' => 19.25,
            'tax_inclusive' => true,
            'allow_pickup' => true,
            'allow_delivery' => true,
        ];
    }
}

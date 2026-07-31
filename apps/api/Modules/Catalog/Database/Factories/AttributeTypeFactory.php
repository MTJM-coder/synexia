<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\AttributeType;
use Modules\Marketplace\Models\Shop;

class AttributeTypeFactory extends Factory
{
    protected $model = AttributeType::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'name' => fake()->randomElement(['Couleur', 'Taille', 'Matière']),
        ];
    }
}

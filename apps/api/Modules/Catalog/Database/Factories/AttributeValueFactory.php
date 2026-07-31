<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\AttributeType;
use Modules\Catalog\Models\AttributeValue;

class AttributeValueFactory extends Factory
{
    protected $model = AttributeValue::class;

    public function definition(): array
    {
        return [
            'attribute_type_id' => AttributeType::factory(),
            'value' => fake()->unique()->word(),
        ];
    }
}

<?php

namespace Modules\Marketplace\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Marketplace\Models\CommissionRule;
use Modules\Marketplace\Models\Shop;

class CommissionRuleFactory extends Factory
{
    protected $model = CommissionRule::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'category_id' => null,
            'rate' => fake()->randomFloat(2, 2, 15),
            'is_active' => true,
        ];
    }

    public function forCategory(int $categoryId): static
    {
        return $this->state(fn () => ['category_id' => $categoryId]);
    }

    public function global(): static
    {
        return $this->state(fn () => ['shop_id' => null]);
    }
}

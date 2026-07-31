<?php

namespace Modules\Catalog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Product;
use Modules\Marketplace\Models\Shop;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'uuid' => (string) Str::uuid(),
            'shop_id' => Shop::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::random(6),
            'description' => fake()->paragraph(),
            'has_variants' => false,
            'base_price' => fake()->randomFloat(2, 500, 50000),
            'status' => Product::STATUS_DRAFT,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => Product::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function withVariants(): static
    {
        return $this->state(fn () => ['has_variants' => true, 'base_price' => 0]);
    }
}

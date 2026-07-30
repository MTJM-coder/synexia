<?php

namespace Modules\Brands\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Brands\Models\Brand;

class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'shop_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
        ];
    }

    public function forShop(int $shopId): static
    {
        return $this->state(fn () => ['shop_id' => $shopId]);
    }
}

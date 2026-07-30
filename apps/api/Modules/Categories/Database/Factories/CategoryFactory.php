<?php

namespace Modules\Categories\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Categories\Models\Category;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'shop_id' => null,
            'parent_id' => null,
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::random(6),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function forShop(int $shopId): static
    {
        return $this->state(fn () => ['shop_id' => $shopId]);
    }

    public function childOf(Category $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id, 'shop_id' => $parent->shop_id]);
    }
}

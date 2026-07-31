<?php

namespace Modules\Catalog\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\AttributeType;
use Modules\Catalog\Models\AttributeValue;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Services\VariantGenerator;
use Tests\TestCase;

class VariantGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private function makeAttributeValues(int $shopId, string $typeName, array $values): array
    {
        $type = AttributeType::factory()->create(['shop_id' => $shopId, 'name' => $typeName]);

        return collect($values)
            ->map(fn (string $value) => AttributeValue::factory()->create([
                'attribute_type_id' => $type->id,
                'value' => $value,
            ])->id)
            ->all();
    }

    public function test_generates_the_cartesian_product_of_two_attribute_types(): void
    {
        $product = Product::factory()->withVariants()->create();

        $colors = $this->makeAttributeValues($product->shop_id, 'Couleur', ['Noir', 'Blanc']);
        $sizes = $this->makeAttributeValues($product->shop_id, 'Taille', ['S', 'M']);

        $created = app(VariantGenerator::class)->generate($product, [$colors, $sizes]);

        $this->assertCount(4, $created); // Noir-S, Noir-M, Blanc-S, Blanc-M
        $this->assertCount(4, $product->variants()->get());
    }

    public function test_is_idempotent_calling_twice_with_the_same_values_creates_nothing_new(): void
    {
        $product = Product::factory()->withVariants()->create();
        $colors = $this->makeAttributeValues($product->shop_id, 'Couleur', ['Noir', 'Blanc']);

        app(VariantGenerator::class)->generate($product, [$colors]);
        $secondCall = app(VariantGenerator::class)->generate($product, [$colors]);

        $this->assertCount(0, $secondCall);
        $this->assertCount(2, $product->variants()->get()); // toujours 2, pas 4
    }

    public function test_adding_a_new_value_only_generates_the_new_combinations(): void
    {
        $product = Product::factory()->withVariants()->create();
        $type = AttributeType::factory()->create(['shop_id' => $product->shop_id, 'name' => 'Couleur']);
        $noir = AttributeValue::factory()->create(['attribute_type_id' => $type->id, 'value' => 'Noir']);
        $blanc = AttributeValue::factory()->create(['attribute_type_id' => $type->id, 'value' => 'Blanc']);

        app(VariantGenerator::class)->generate($product, [[$noir->id, $blanc->id]]);
        $existingVariantIds = $product->variants()->pluck('id')->sort()->values();

        // On ajoute Rouge après coup.
        $rouge = AttributeValue::factory()->create(['attribute_type_id' => $type->id, 'value' => 'Rouge']);
        $created = app(VariantGenerator::class)->generate($product, [[$noir->id, $blanc->id, $rouge->id]]);

        $this->assertCount(1, $created); // seulement la nouvelle combinaison Rouge
        // Les variantes Noir et Blanc d'origine n'ont pas été touchées (mêmes IDs).
        $this->assertEquals(
            $existingVariantIds->all(),
            $product->variants()->whereIn('id', $existingVariantIds)->pluck('id')->sort()->values()->all(),
        );
    }

    public function test_never_combines_two_values_of_the_same_attribute_type_together(): void
    {
        $product = Product::factory()->withVariants()->create();
        $colors = $this->makeAttributeValues($product->shop_id, 'Couleur', ['Noir', 'Blanc']);

        $created = app(VariantGenerator::class)->generate($product, [$colors]);

        foreach ($created as $variant) {
            // Une seule valeur d'attribut par variante ici (un seul type fourni).
            $this->assertCount(1, $variant->attributeValues);
        }
    }

    public function test_ensures_exactly_one_default_variant_after_generation(): void
    {
        $product = Product::factory()->withVariants()->create();
        $colors = $this->makeAttributeValues($product->shop_id, 'Couleur', ['Noir', 'Blanc']);

        app(VariantGenerator::class)->generate($product, [$colors]);

        $this->assertSame(1, $product->variants()->where('is_default', true)->count());
    }

    public function test_generating_with_no_values_creates_nothing(): void
    {
        $product = Product::factory()->withVariants()->create();

        $created = app(VariantGenerator::class)->generate($product, [[]]);

        $this->assertCount(0, $created);
    }

    public function test_each_generated_variant_gets_a_unique_sku(): void
    {
        $product = Product::factory()->withVariants()->create();
        $colors = $this->makeAttributeValues($product->shop_id, 'Couleur', ['Noir', 'Blanc', 'Rouge']);

        $created = app(VariantGenerator::class)->generate($product, [$colors]);

        $skus = $created->pluck('sku');
        $this->assertCount($skus->count(), $skus->unique());
    }
}

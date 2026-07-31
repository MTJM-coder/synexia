<?php

namespace Modules\Catalog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Product;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_a_published_product_by_a_word_in_its_description(): void
    {
        Product::factory()->published()->create([
            'name' => 'Chaussures élégantes',
            'description' => 'Fabriquées en cuir véritable, finition artisanale.',
        ]);

        $response = $this->getJson('/api/v1/products/search?q=cuir');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Chaussures élégantes');
    }

    public function test_search_excludes_draft_products(): void
    {
        Product::factory()->create([ // status par défaut = draft
            'name' => 'Produit non publié',
            'description' => 'Contient le mot recherche ici.',
        ]);

        $this->getJson('/api/v1/products/search?q=recherche')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_search_requires_a_minimum_query_length(): void
    {
        $this->getJson('/api/v1/products/search?q=a')->assertStatus(422);
    }
}

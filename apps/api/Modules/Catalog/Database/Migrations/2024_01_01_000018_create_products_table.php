<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('name', 255);
            $table->string('slug', 280);
            $table->text('description')->nullable();
            $table->string('short_description', 500)->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->string('qr_code_path', 255)->nullable();
            $table->boolean('has_variants')->default(false);
            $table->decimal('base_price', 12, 2)->default(0);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->unsignedInteger('weight_grams')->nullable();
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
            $table->enum('status', ['draft', 'published', 'archived', 'out_of_stock'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('sold_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
            $table->unique(['slug', 'shop_id'], 'uq_product_slug_shop');
        });

        // $table->fullText(['name', 'description']) est invalide sur Postgres :
        // la grammaire Laravel ne supporte le fullText() multi-colonnes que sur
        // MySQL. On crée donc l'index GIN directement, sur la concaténation des
        // deux colonnes (équivalent fonctionnel, valable uniquement sur Postgres).
        DB::statement(
            "CREATE INDEX ft_product_search ON products ".
            "USING GIN (to_tsvector('french', coalesce(name, '') || ' ' || coalesce(description, '')))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
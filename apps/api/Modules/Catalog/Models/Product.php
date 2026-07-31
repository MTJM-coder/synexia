<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Brands\Models\Brand;
use Modules\Categories\Models\Category;
use Modules\Marketplace\Models\Shop;
use Modules\Suppliers\Models\Supplier;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_OUT_OF_STOCK = 'out_of_stock';

    protected $fillable = [
        'uuid', // CORRIGÉ : absent avant, silencieusement ignoré par Model::create()
        'shop_id',
        'category_id',
        'subcategory_id',
        'brand_id',
        'supplier_id',
        'name',
        'slug',
        'description',
        'short_description',
        'sku',
        'barcode',
        'qr_code_path',
        'has_variants',
        'base_price',
        'compare_at_price',
        'cost_price',
        'tax_rate',
        'weight_grams',
        'length_cm',
        'width_cm',
        'height_cm',
        'status',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'has_variants' => 'boolean',
            'is_featured' => 'boolean',
            'base_price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'average_rating' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideo::class)->orderBy('sort_order');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function hasBeenUsed(): bool
    {
        // NOTE : toujours false tant qu'Inventory/Sales n'existent pas — voir
        // itération précédente pour le détail de cette décision.
        return false;
    }
}

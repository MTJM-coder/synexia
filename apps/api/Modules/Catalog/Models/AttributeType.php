<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Marketplace\Models\Shop;

class AttributeType extends Model
{
    use HasFactory;

    // Migration : created_at seul, pas d'updated_at (même piège que sur Brands).
    const UPDATED_AT = null;

    protected $fillable = [
        'shop_id',
        'name',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }
}

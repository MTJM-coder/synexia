<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AttributeValue extends Model
{
    use HasFactory;

    // CORRIGÉ : la migration n'a NI created_at NI updated_at (contrairement
    // à AttributeType/ProductImage/ProductVideo qui ont created_at seul) —
    // il faut désactiver les timestamps entièrement, pas juste UPDATED_AT.
    public $timestamps = false;

    protected $fillable = [
        'attribute_type_id',
        'value',
        'hex_color',
    ];

    public function attributeType(): BelongsTo
    {
        return $this->belongsTo(AttributeType::class);
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'product_variant_attribute_values',
        );
    }
}

<?php

namespace Modules\Brands\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Marketplace\Models\Shop;

class Brand extends Model
{
    use HasFactory;

    /**
     * Le schéma d'origine n'a que `created_at`, pas `updated_at` — on informe
     * Eloquent de ne gérer que la première, sinon toute sauvegarde échoue en
     * tentant d'écrire dans une colonne `updated_at` qui n'existe pas.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'shop_id',
        'name',
        'slug',
        'logo_path',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function isGlobal(): bool
    {
        return $this->shop_id === null;
    }
}

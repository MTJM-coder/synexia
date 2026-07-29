<?php

namespace Modules\Identity\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const GUARD_PLATFORM = 'platform';
    public const GUARD_SHOP = 'shop';

    protected $fillable = [
        'name',
        'slug',
        'guard_scope',
        'shop_id',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function shopEmployees(): HasMany
    {
        return $this->hasMany(ShopEmployee::class, 'role_id');
    }

    /**
     * Un rôle "système" (Owner, Manager, Employé de base...) ne peut pas être
     * supprimé ni renommé par une boutique — seuls les rôles custom le peuvent.
     */
    public function isEditable(): bool
    {
        return ! $this->is_system;
    }
}

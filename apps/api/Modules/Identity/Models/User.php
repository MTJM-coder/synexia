<?php

namespace Modules\Identity\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Marketplace\Models\Shop;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'avatar_path',
        'is_super_admin',
        'status',
        'locale',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'is_super_admin' => 'boolean',
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Boutiques dont cet utilisateur est propriétaire.
     */
    public function ownedShops(): HasMany
    {
        return $this->hasMany(Shop::class, 'owner_id');
    }

    /**
     * Toutes les relations d'emploi de cet utilisateur (une par boutique où il travaille).
     */
    public function shopEmployments(): HasMany
    {
        return $this->hasMany(ShopEmployee::class, 'user_id');
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}

<?php

namespace Modules\Identity\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'avatar_path',
        'locale',
        'status',
        'is_super_admin',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'is_super_admin' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function shopEmployees(): HasMany
    {
        return $this->hasMany(ShopEmployee::class);
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }
}

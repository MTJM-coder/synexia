<?php

namespace Modules\Identity\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Marketplace\Models\Shop;

class ShopEmployee extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'shop_id',
        'user_id',
        'role_id',
        'job_title',
        'status',
        'hired_at',
    ];

    protected function casts(): array
    {
        return [
            'hired_at' => 'date',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Surcharges de permissions individuelles (grant/deny ponctuels),
     * en plus de celles héritées du rôle.
     */
    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(ShopEmployeePermission::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}

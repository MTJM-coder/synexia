<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Identity\Models\User;

class Shop extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'uuid',
        'owner_id',
        'subscription_plan_id',
        'name',
        'slug',
        'slogan',
        'description',
        'logo_path',
        'banner_path',
        'email',
        'phone',
        'whatsapp',
        'website',
        'social_links',
        'address_line',
        'city',
        'region',
        'country',
        'latitude',
        'longitude',
        'status',
        'is_featured',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_featured' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ShopSubscription::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(ShopSetting::class);
    }

    public function commissionRules(): HasMany
    {
        return $this->hasMany(CommissionRule::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}

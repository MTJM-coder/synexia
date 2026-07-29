<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Marketplace\Domain\ValueObjects\PlanLimits;

class SubscriptionPlan extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_period',
        'max_products',
        'max_employees',
        'max_warehouses',
        'commission_rate',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class, 'subscription_plan_id');
    }

    public function toLimits(): PlanLimits
    {
        return new PlanLimits(
            maxProducts: $this->max_products,
            maxEmployees: $this->max_employees,
            maxWarehouses: $this->max_warehouses,
        );
    }
}

<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'currency',
        'language',
        'timezone',
        'tax_rate',
        'tax_inclusive',
        'opening_hours',
        'allow_pickup',
        'allow_delivery',
        'delivery_radius_km',
        'email_config',
        'sms_config',
        'whatsapp_config',
        'notification_preferences',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'tax_inclusive' => 'boolean',
            'opening_hours' => 'array',
            'allow_pickup' => 'boolean',
            'allow_delivery' => 'boolean',
            'delivery_radius_km' => 'decimal:2',
            'email_config' => 'array',
            'sms_config' => 'array',
            'whatsapp_config' => 'array',
            'notification_preferences' => 'array',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}

<?php

namespace Modules\Identity\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopEmployeePermission extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'shop_employee_id',
        'permission_id',
        'is_granted',
    ];

    protected function casts(): array
    {
        return [
            'is_granted' => 'boolean',
        ];
    }

    public function shopEmployee(): BelongsTo
    {
        return $this->belongsTo(ShopEmployee::class);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}

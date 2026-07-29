<?php

namespace Modules\Marketplace\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Events\ShopStatusChanged;

class LogShopStatusChange
{
    public function handle(ShopStatusChanged $event): void
    {
        DB::table('activity_logs')->insert([
            'shop_id' => $event->shop->id,
            'user_id' => $event->changedByUserId,
            'action' => 'shop.status_changed',
            'subject_type' => 'Shop',
            'subject_id' => $event->shop->id,
            'description' => "Statut de la boutique : {$event->previousStatus} → {$event->newStatus}",
            'old_values' => json_encode(['status' => $event->previousStatus]),
            'new_values' => json_encode(['status' => $event->newStatus]),
            'created_at' => now(),
        ]);
    }
}

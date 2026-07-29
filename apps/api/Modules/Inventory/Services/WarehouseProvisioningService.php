<?php

namespace Modules\Inventory\Services;

use Modules\Inventory\Contracts\WarehouseProvisioningContract;
use Modules\Inventory\Models\Warehouse;

class WarehouseProvisioningService implements WarehouseProvisioningContract
{
    public function createDefaultWarehouse(int $shopId, string $name = 'Entrepôt principal'): int
    {
        $warehouse = Warehouse::create([
            'shop_id' => $shopId,
            'name' => $name,
            'code' => 'WH-'.strtoupper(substr(md5($shopId.$name.now()->timestamp), 0, 6)),
            'is_default' => true,
            'is_active' => true,
        ]);

        return $warehouse->id;
    }
}

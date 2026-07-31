<?php

namespace Modules\Catalog\Actions;

use Modules\Catalog\Events\ProductArchived;
use Modules\Catalog\Models\Product;

class ArchiveProductAction
{
    public function execute(Product $product): Product
    {
        $product->update(['status' => Product::STATUS_ARCHIVED]);

        ProductArchived::dispatch($product);

        return $product->fresh();
    }
}

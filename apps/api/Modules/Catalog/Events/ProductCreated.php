<?php

namespace Modules\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Catalog\Models\Product;

class ProductCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Product $product,
    ) {
    }
}

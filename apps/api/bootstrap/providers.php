<?php
use App\Providers\AppServiceProvider;
use Modules\Sales\SalesServiceProvider;
use  Modules\Categories\CategoriesServiceProvider;
use Modules\Accounting\AccountingServiceProvider;
use Modules\Analytics\AnalyticsServiceProvider;
use Modules\Brands\BrandsServiceProvider;
use Modules\Catalog\CatalogServiceProvider;
use Modules\Identity\IdentityServiceProvider;
use Modules\Inventory\InventoryServiceProvider;
use Modules\Marketing\MarketingServiceProvider;
use Modules\Marketplace\MarketplaceServiceProvider;
use Modules\Messaging\MessagingServiceProvider;
use Modules\Notifications\NotificationsServiceProvider;
use Modules\Payments\PaymentsServiceProvider;
use Modules\Reviews\ReviewsServiceProvider;
use Modules\Shipping\ShippingServiceProvider;
use Modules\Suppliers\SuppliersServiceProvider;
return [
    AppServiceProvider::class,
    SalesServiceProvider::class,
    CategoriesServiceProvider::class,
    AccountingServiceProvider::class,
    AnalyticsServiceProvider::class,
    BrandsServiceProvider::class,
    CatalogServiceProvider::class,
    IdentityServiceProvider::class,
    InventoryServiceProvider::class,
    MarketingServiceProvider::class,
    MarketplaceServiceProvider::class,
    MessagingServiceProvider::class,
    NotificationsServiceProvider::class,
    PaymentsServiceProvider::class,
    ReviewsServiceProvider::class,
    ShippingServiceProvider::class,
    SuppliersServiceProvider::class
    
];

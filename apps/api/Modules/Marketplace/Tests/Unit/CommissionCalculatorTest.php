<?php

namespace Modules\Marketplace\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Marketplace\Contracts\CommissionCalculatorContract;
use Modules\Marketplace\Contracts\PlanLimitCheckerContract;
use Modules\Marketplace\Exceptions\PlanLimitExceededException;
use Modules\Marketplace\Models\CommissionRule;
use Modules\Marketplace\Models\Shop;
use Modules\Marketplace\Models\SubscriptionPlan;
use Tests\TestCase;

class CommissionCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_falls_back_to_plan_rate_when_no_rule_exists(): void
    {
        $plan = SubscriptionPlan::factory()->create(['commission_rate' => 7]);
        $shop = Shop::factory()->create(['subscription_plan_id' => $plan->id]);

        $rate = $this->calculator()->rateFor($shop);

        $this->assertSame(7.0, $rate->percentage);
    }

    public function test_shop_wide_rule_overrides_plan_default(): void
    {
        $plan = SubscriptionPlan::factory()->create(['commission_rate' => 7]);
        $shop = Shop::factory()->create(['subscription_plan_id' => $plan->id]);

        CommissionRule::factory()->create(['shop_id' => $shop->id, 'category_id' => null, 'rate' => 4]);

        $rate = $this->calculator()->rateFor($shop);

        $this->assertSame(4.0, $rate->percentage);
    }

    public function test_shop_plus_category_rule_has_highest_priority(): void
    {
        $plan = SubscriptionPlan::factory()->create(['commission_rate' => 7]);
        $shop = Shop::factory()->create(['subscription_plan_id' => $plan->id]);
        $categoryId = 42;

        CommissionRule::factory()->create(['shop_id' => $shop->id, 'category_id' => null, 'rate' => 4]);
        CommissionRule::factory()->create(['shop_id' => $shop->id, 'category_id' => $categoryId, 'rate' => 2]);

        $rateForThatCategory = $this->calculator()->rateFor($shop, $categoryId);
        $rateForAnotherCategory = $this->calculator()->rateFor($shop, 999);

        $this->assertSame(2.0, $rateForThatCategory->percentage);
        $this->assertSame(4.0, $rateForAnotherCategory->percentage); // retombe sur la règle boutique
    }

    public function test_inactive_rule_is_ignored(): void
    {
        $plan = SubscriptionPlan::factory()->create(['commission_rate' => 7]);
        $shop = Shop::factory()->create(['subscription_plan_id' => $plan->id]);

        CommissionRule::factory()->create(['shop_id' => $shop->id, 'category_id' => null, 'rate' => 4, 'is_active' => false]);

        $rate = $this->calculator()->rateFor($shop);

        $this->assertSame(7.0, $rate->percentage); // règle inactive ignorée, repli sur le plan
    }

    public function test_calculate_amount_applies_the_resolved_rate(): void
    {
        $plan = SubscriptionPlan::factory()->create(['commission_rate' => 10]);
        $shop = Shop::factory()->create(['subscription_plan_id' => $plan->id]);

        $amount = $this->calculator()->calculateAmount($shop, 1000);

        $this->assertSame(100.0, $amount);
    }

    public function test_plan_limit_checker_throws_when_product_limit_reached(): void
    {
        $plan = SubscriptionPlan::factory()->create(['max_products' => 5]);
        $shop = Shop::factory()->create(['subscription_plan_id' => $plan->id]);

        $this->expectException(PlanLimitExceededException::class);

        $this->limitChecker()->assertCanAddProduct($shop, currentProductCount: 5);
    }

    public function test_plan_limit_checker_allows_unlimited_plan(): void
    {
        $plan = SubscriptionPlan::factory()->unlimited()->create();
        $shop = Shop::factory()->create(['subscription_plan_id' => $plan->id]);

        $this->limitChecker()->assertCanAddProduct($shop, currentProductCount: 100000);

        $this->assertTrue(true); // n'a pas levé d'exception
    }

    private function calculator(): CommissionCalculatorContract
    {
        return $this->app->make(CommissionCalculatorContract::class);
    }

    private function limitChecker(): PlanLimitCheckerContract
    {
        return $this->app->make(PlanLimitCheckerContract::class);
    }
}

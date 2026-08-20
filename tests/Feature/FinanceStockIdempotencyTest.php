<?php

namespace Tests\Feature;

use App\Models\AmazonOrder;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceStockIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_return_status_does_not_double_restock(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);
        $user->assignRole('administrator');

        $vendor = Vendor::factory()->create();

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'product_name' => 'Test Product',
            'asin' => 'B00TEST123',
            'buying_price' => 10,
            'fba_fee' => 2,
            'shipping_cost' => 1,
            'labeling_cost' => 0,
            'other_costs' => 0,
            'operation_cost' => 0,
            'amazon_sell_price' => 25,
            'referral_fee_percent' => 15,
            'status' => 'active',
            'stock_quantity' => 95,
        ]);

        $order = AmazonOrder::create([
            'product_id' => $product->id,
            'vendor_id' => $vendor->id,
            'product_name' => $product->product_name,
            'asin' => $product->asin,
            'fulfillment_channel' => 'FBA',
            'amazon_marketplace' => 'US',
            'order_date' => now()->toDateString(),
            'order_status' => 'delivered',
            'quantity' => 5,
            'sale_price' => 25,
            'total_revenue' => 125,
            'product_cost' => 50,
            'fba_fee' => 10,
            'shipping_cost' => 5,
            'labeling_cost' => 0,
            'other_costs' => 0,
            'operation_cost' => 0,
            'amazon_referral_fee' => 0,
            'breakaway_referral_rate' => 15,
        ]);

        $this->actingAs($user);

        $this->post(route('finance.sales.status', $order->id), ['order_status' => 'returned'])
            ->assertRedirect();
        $this->assertSame(100, $product->fresh()->stock_quantity);

        $this->post(route('finance.sales.status', $order->id), ['order_status' => 'returned'])
            ->assertRedirect();
        $this->assertSame(100, $product->fresh()->stock_quantity);

        $this->post(route('finance.sales.status', $order->id), ['order_status' => 'delivered'])
            ->assertRedirect();
        $this->assertSame(95, $product->fresh()->stock_quantity);
    }
}

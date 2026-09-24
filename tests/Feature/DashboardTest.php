<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private Product $product;
    private PaymentMethod $cash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::create(['name' => 'Rina', 'pin_hash' => '123450', 'role' => 'CASHIER']);
        $cat = Category::create(['name' => 'Kopi', 'sort_order' => 1]);
        $this->product = Product::create(['category_id' => $cat->id, 'name' => 'Latte', 'sku' => 'LAT', 'price' => 25000, 'status' => 'ACTIVE']);
        $this->cash = PaymentMethod::create(['name' => 'Cash', 'code' => 'CASH', 'status' => 'ACTIVE']);

        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '123450']);
        $this->post('/shift/open', ['opening_cash' => 500000]);
    }

    public function test_dashboard_shows_stats_and_charts(): void
    {
        $this->postJson('/transactions', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 50000,
        ])->assertStatus(201);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Penjualan 7 Hari Terakhir')
            ->assertSee('Top Produk')
            ->assertSee('50.000')
            ->assertSee('dailyChart')
            ->assertSee('topProductsChart');
    }

    public function test_dashboard_hides_other_cashiers_sales(): void
    {
        $other = User::create(['name' => 'Budi', 'pin_hash' => '123451', 'role' => 'CASHIER']);
        $this->post('/logout');
        $this->post('/login', ['user_id' => $other->id, 'pin' => '123451']);
        $this->post('/shift/open', ['opening_cash' => 100000]);

        $this->postJson('/transactions', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 25000,
        ])->assertStatus(201);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('25.000')
            ->assertDontSee('50.000');
    }
}
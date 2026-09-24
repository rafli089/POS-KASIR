<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private User $manager;
    private Product $product;
    private Product $unlimited;
    private PaymentMethod $cash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::create(['name' => 'Rina', 'pin_hash' => '123450', 'role' => 'CASHIER']);
        $this->manager = User::create(['name' => 'Mira', 'pin_hash' => '123456', 'role' => 'MANAGER']);

        $cat = Category::create(['name' => 'Kopi', 'sort_order' => 1]);
        $this->product = Product::create(['category_id' => $cat->id, 'name' => 'Latte', 'sku' => 'LAT', 'price' => 25000, 'stock' => 10, 'status' => 'ACTIVE']);
        $this->unlimited = Product::create(['category_id' => $cat->id, 'name' => 'Teh', 'sku' => 'TEH', 'price' => 15000, 'stock' => null, 'status' => 'ACTIVE']);
        $this->cash = PaymentMethod::create(['name' => 'Cash', 'code' => 'CASH', 'status' => 'ACTIVE']);

        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '123450']);
        $this->post('/shift/open', ['opening_cash' => 500000]);
    }

    public function test_sale_decrements_stock(): void
    {
        $this->postJson('/transactions', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 3]],
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 75000,
        ])->assertStatus(201);

        $this->assertSame(7, $this->product->fresh()->stock);
    }

    public function test_insufficient_stock_rejected(): void
    {
        $this->postJson('/transactions', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 11]],
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 275000,
        ])->assertStatus(422);

        $this->assertSame(10, $this->product->fresh()->stock);
    }

    public function test_zero_stock_product_not_sellable(): void
    {
        $this->product->update(['stock' => 0]);

        $this->postJson('/transactions', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 25000,
        ])->assertStatus(422);
    }

    public function test_unlimited_stock_never_decrements(): void
    {
        $this->postJson('/transactions', [
            'items' => [['product_id' => $this->unlimited->id, 'quantity' => 5]],
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 75000,
        ])->assertStatus(201);

        $this->assertNull($this->unlimited->fresh()->stock);
    }

    public function test_void_restores_stock(): void
    {
        $this->postJson('/transactions', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 4]],
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 100000,
        ])->assertStatus(201);

        $txId = \App\Models\Transaction::first()->id;

        $this->assertSame(6, $this->product->fresh()->stock);

        $this->post(route('transactions.void', $txId), ['reason' => 'Salah input']);

        $this->assertSame(10, $this->product->fresh()->stock);
    }

    public function test_admin_can_set_stock(): void
    {
        $this->post('/logout');
        $this->post('/login', ['user_id' => $this->manager->id, 'pin' => '123456']);

        $this->put(route('products.update', $this->product), [
            'category_id' => $this->product->category_id,
            'name' => 'Latte',
            'sku' => 'LAT',
            'price' => 25000,
            'stock' => 99,
            'status' => 'ACTIVE',
        ]);

        $this->assertSame(99, $this->product->fresh()->stock);
    }
}
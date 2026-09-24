<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceChargeTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private User $admin;
    private \App\Models\Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::create(['name' => 'Rina', 'pin_hash' => '123450', 'role' => 'CASHIER']);
        $this->admin   = User::create(['name' => 'Admin', 'pin_hash' => '123456', 'role' => 'ADMIN']);

        $cat = Category::create(['name' => 'Kopi', 'sort_order' => 1]);
        $this->product = Product::create(['category_id' => $cat->id, 'name' => 'Latte', 'sku' => 'LAT', 'price' => 25000, 'status' => 'ACTIVE']);
        $this->cash = PaymentMethod::create(['name' => 'Cash', 'code' => 'CASH', 'status' => 'ACTIVE']);

        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '123450']);
        $this->post('/shift/open', ['opening_cash' => 500000]);
    }

    public function test_admin_can_save_service_charge(): void
    {
        $this->post('/logout');
        $this->post('/login', ['user_id' => $this->admin->id, 'pin' => '123456']);

        $this->post(route('settings.update'), ['service_charge_percent' => 5])
            ->assertRedirect(route('settings.index'));

        $this->assertSame('5', Setting::value(Setting::KEY_SERVICE_CHARGE_PERCENT));
    }

    public function test_cashier_cannot_access_settings(): void
    {
        $this->get(route('settings.index'))->assertRedirect(route('pos.index'));
    }

    public function test_transaction_includes_service_charge(): void
    {
        Setting::set(Setting::KEY_SERVICE_CHARGE_PERCENT, '10');

        $this->postJson('/transactions', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]], // 50000
            'discount' => 0, 'tax' => 0,
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 55000,
        ])->assertStatus(201);

        $tx = Transaction::first();
        $this->assertSame(5000, $tx->service_charge);
        $this->assertSame(55000, $tx->grand_total);
    }

    public function test_transaction_without_service_charge_setting(): void
    {
        $this->postJson('/transactions', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'discount' => 0, 'tax' => 0,
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 25000,
        ])->assertStatus(201);

        $tx = Transaction::first();
        $this->assertSame(0, $tx->service_charge);
        $this->assertSame(25000, $tx->grand_total);
    }

    public function test_service_charge_surfaces_on_pos_view(): void
    {
        Setting::set(Setting::KEY_SERVICE_CHARGE_PERCENT, '5');
        $this->get('/pos')->assertOk()->assertSee('Layanan (5%)');
    }
}
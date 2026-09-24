<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private Product $espresso;
    private Product $latte;
    private PaymentMethod $cash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::create(['name' => 'Rina', 'pin_hash' => '123450', 'role' => 'CASHIER']);
        User::create(['name' => 'Admin', 'pin_hash' => '123456', 'role' => 'ADMIN']);

        $cat = \App\Models\Category::create(['name' => 'Kopi', 'sort_order' => 1]);
        $this->espresso = Product::create(['category_id' => $cat->id, 'name' => 'Espresso', 'sku' => 'ESP', 'price' => 18000, 'status' => 'ACTIVE']);
        $this->latte = Product::create(['category_id' => $cat->id, 'name' => 'Cafe Latte', 'sku' => 'LATTE', 'price' => 26000, 'status' => 'ACTIVE']);

        $this->cash = PaymentMethod::create(['name' => 'Cash', 'code' => 'CASH', 'status' => 'ACTIVE']);
        PaymentMethod::create(['name' => 'QRIS', 'code' => 'QRIS', 'status' => 'ACTIVE']);
    }

    public function test_login_rejects_wrong_pin(): void
    {
        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '0000'])
            ->assertSessionHasErrors('pin');
    }

    public function test_full_flow_login_to_dashboard(): void
    {
        // Login
        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '123450'])
            ->assertRedirect(route('shift.open'));

        // No shift yet: transaction blocked
        $this->postJson('/transactions', [
            'items' => [['product_id' => $this->espresso->id, 'quantity' => 1]],
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 20000,
        ])->assertStatus(422);

        // Open shift
        $this->post('/shift/open', ['opening_cash' => 500000])
            ->assertRedirect(route('pos.index'));

        $this->assertDatabaseHas('shifts', [
            'user_id' => $this->cashier->id,
            'status' => Shift::STATUS_OPEN,
            'opening_cash' => 500000,
        ]);

        // POS renders products
        $this->get(route('pos.index'))->assertOk()->assertSee('Espresso');

        // Create transaction (2x Espresso + 1x Latte = 36000 + 26000 = 62000)
        $this->postJson('/transactions', [
            'items' => [
                ['product_id' => $this->espresso->id, 'quantity' => 2],
                ['product_id' => $this->latte->id, 'quantity' => 1],
            ],
            'discount' => 0,
            'tax' => 0,
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 70000,
        ])->assertStatus(201)->assertJsonStructure(['transaction' => ['transaction_number', 'grand_total']]);

        $tx = Transaction::where('user_id', $this->cashier->id)->first();
        $this->assertSame(62000, $tx->grand_total);
        $this->assertSame(8000, $tx->change_amount);
        $this->assertSame('COMPLETED', $tx->status);
        $this->assertCount(2, $tx->items);
        $this->assertSame('Espresso', $tx->items()->where('product_id', $this->espresso->id)->first()->product_name);
        $this->assertDatabaseHas('receipts', ['transaction_id' => $tx->id, 'print_count' => 1]);

        // Struk checker
        $this->get(route('transactions.index'))->assertOk()->assertSee('POS-'.now()->format('Ymd'));

        // Activity recorded
        $this->assertDatabaseHas('shift_activities', [
            'shift_id' => $tx->shift_id,
            'activity_type' => 'TRANSACTION_CREATED',
        ]);

        // Close shift
        $this->get(route('shift.close'))->assertOk()->assertSee('Kas aktual');

        $this->post('/shift/close', ['actual_cash' => 562000])
            ->assertRedirect();

        $shift = Shift::first();
        $this->assertSame(Shift::STATUS_CLOSED, $shift->status);
        $this->assertSame(562000, $shift->expected_cash + $shift->cash_difference);

        // Dashboard works after close & shows today's total
        $this->get(route('dashboard'))->assertOk()->assertSee('Total Penjualan');

        // Shift report
        $this->get(route('shift.report', ['shift' => $shift->id]))->assertOk()->assertSee('Laporan Shift');

        // Closed shift blocks new transactions
        $this->postJson('/transactions', [
            'items' => [['product_id' => $this->espresso->id, 'quantity' => 1]],
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 20000,
        ])->assertStatus(422);

        // Logout
        $this->post('/logout')->assertRedirect(route('login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
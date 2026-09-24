<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin   = User::create(['name' => 'Admin',   'pin_hash' => '123456', 'role' => 'ADMIN']);
        $this->manager = User::create(['name' => 'Manager', 'pin_hash' => '123456', 'role' => 'MANAGER']);
        $this->cashier = User::create(['name' => 'Rina',    'pin_hash' => '123450', 'role' => 'CASHIER']);

        $cat = Category::create(['name' => 'Kopi', 'sort_order' => 1]);
        $product = Product::create(['category_id' => $cat->id, 'name' => 'Espresso', 'sku' => 'ESP', 'price' => 18000, 'status' => 'ACTIVE']);
        $this->cashMethod = PaymentMethod::create(['name' => 'Cash', 'code' => 'CASH', 'status' => 'ACTIVE']);

        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '123450']);
        $this->post('/shift/open', ['opening_cash' => 500000]);
        $this->postJson('/transactions', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'discount' => 0, 'tax' => 0,
            'payment_method_id' => $this->cashMethod->id,
            'payment_amount' => 40000,
        ]);
    }

    private function refundAs(User $user, string $pin, array $params = ['amount' => 36000, 'reason' => 'pelanggan batal beli'])
    {
        $this->post('/logout');
        $this->post('/login', ['user_id' => $user->id, 'pin' => $pin]);
        $this->session(['shift_id' => Transaction::first()->shift_id]);

        return $this->post(route('transactions.refund', Transaction::first()), $params);
    }

    public function test_cashier_cannot_refund(): void
    {
        $tx = Transaction::first();
        $this->post(route('transactions.refund', $tx), ['amount' => 36000, 'reason' => 'x'])
            ->assertSessionHasErrors('refund');

        $this->assertSame('COMPLETED', $tx->fresh()->status);
    }

    public function test_manager_can_refund(): void
    {
        $this->refundAs($this->manager, '123456')->assertRedirect();

        $tx = Transaction::first()->fresh();
        $this->assertSame('REFUNDED', $tx->status);
        $this->assertDatabaseHas('shift_activities', [
            'activity_type' => 'TRANSACTION_REFUND',
            'reference_amount' => 36000,
        ]);
    }

    public function test_refund_rejects_already_refunded(): void
    {
        Transaction::first()->update(['status' => Transaction::STATUS_REFUNDED]);

        $this->refundAs($this->admin, '123456')
            ->assertSessionHasErrors('refund');
    }

    public function test_refund_rejects_amount_greater_than_total(): void
    {
        $this->refundAs($this->admin, '123456', ['amount' => 999999, 'reason' => 'x'])
            ->assertSessionHasErrors('amount');
    }

    public function test_refund_excludes_from_expected_cash(): void
    {
        $shiftId = Transaction::first()->shift_id;

        $this->post('/logout');
        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '123450']);
        $this->post('/shift/open', ['opening_cash' => 500000]);
        $this->session(['shift_id' => $shiftId]);

        // Refund as manager so role check passes; both manager & cashier must be in same shift
        $this->post('/logout');
        $this->post('/login', ['user_id' => $this->manager->id, 'pin' => '123456']);
        $this->session(['shift_id' => $shiftId]);

        $this->post(route('transactions.refund', Transaction::first()), ['amount' => 36000, 'reason' => 'batal'])
            ->assertRedirect();

        // Back as cashier (shift owner) to view current shift summary
        $this->post('/logout');
        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '123450']);
        $this->session(['shift_id' => $shiftId]);

        // Opening 500000 + cashSales 0 + refund 36000 removed → 464.000
        $this->get('/shift/current')->assertOk()->assertSee('464.000');
    }
}
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoidTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cashier;
    private Product $product;
    private \App\Models\PaymentMethod $cashMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create(['name' => 'Admin', 'pin_hash' => '123456', 'role' => 'ADMIN']);
        $this->cashier = User::create(['name' => 'Rina', 'pin_hash' => '123450', 'role' => 'CASHIER']);

        $cat = Category::create(['name' => 'Kopi', 'sort_order' => 1]);
        $this->product = Product::create(['category_id' => $cat->id, 'name' => 'Espresso', 'sku' => 'ESP', 'price' => 18000, 'status' => 'ACTIVE']);
        $this->cashMethod = \App\Models\PaymentMethod::create(['name' => 'Cash', 'code' => 'CASH', 'status' => 'ACTIVE']);
    }

    private function login(User $user, string $pin): void
    {
        $this->post('/login', ['user_id' => $user->id, 'pin' => $pin]);
    }

    private function createTransaction(User $user, string $pin): Transaction
    {
        $this->login($user, $pin);
        $this->post('/shift/open', ['opening_cash' => 500000]);
        $this->postJson('/transactions', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
            'discount' => 0, 'tax' => 0,
            'payment_method_id' => $this->cashMethod->id,
            'payment_amount' => 40000,
        ]);

        return Transaction::first();
    }

    public function test_cashier_can_void_their_own_transaction(): void
    {
        $tx = $this->createTransaction($this->cashier, '123450');
        $this->assertNotNull($tx);

        $this->post(route('transactions.void', $tx), ['reason' => 'wrong product'])
            ->assertRedirect(route('transactions.show', $tx));

        $this->assertDatabaseHas('transactions', ['id' => $tx->id, 'status' => 'VOID']);
        $this->assertDatabaseHas('shift_activities', ['activity_type' => 'TRANSACTION_VOID']);
    }

    public function test_void_rejects_already_voided(): void
    {
        $tx = $this->createTransaction($this->cashier, '123450');
        $tx->update(['status' => Transaction::STATUS_VOID]);

        $this->post(route('transactions.void', $tx), ['reason' => 'again'])
            ->assertSessionHasErrors('void');
    }

    public function test_void_requires_reason(): void
    {
        $tx = $this->createTransaction($this->cashier, '123450');

        $this->post(route('transactions.void', $tx), [])
            ->assertSessionHasErrors('reason');
    }

    public function test_admin_can_void_in_current_shift(): void
    {
        $tx = $this->createTransaction($this->cashier, '123450');

        $this->login($this->admin, '123456');
        $this->session(['shift_id' => $tx->shift_id]);

        $this->post(route('transactions.void', $tx), ['reason' => 'manager override'])
            ->assertRedirect();

        $this->assertDatabaseHas('transactions', ['id' => $tx->id, 'status' => 'VOID']);
    }
}
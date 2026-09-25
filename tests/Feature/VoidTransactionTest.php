<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionAuthorization;
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

    private function requestVoid(Transaction $tx): TransactionAuthorization
    {
        $this->post(route('transactions.authorize', $tx), [
            'action' => 'VOID',
            'transaction_code' => $tx->transaction_number,
            'reason' => 'wrong product',
        ])->assertRedirect();

        return TransactionAuthorization::where('transaction_id', $tx->id)->where('action', 'VOID')->firstOrFail();
    }

    private function approve(Transaction $tx): string
    {
        $auth = TransactionAuthorization::where('transaction_id', $tx->id)->where('action', 'VOID')->firstOrFail();

        $this->post('/logout');
        $this->login($this->admin, '123456');
        $this->session(['shift_id' => $tx->shift_id]);

        $this->post(route('transactions.approve', $tx))->assertRedirect();

        return $this->app['session']->get('authorization_otp');
    }

    private function executeVoid(Transaction $tx, string $otp): void
    {
        $this->post('/logout');
        $this->login($this->cashier, '123450');
        $this->session(['shift_id' => $tx->shift_id]);

        $this->post(route('transactions.void', $tx), ['otp' => $otp])
            ->assertRedirect(route('transactions.show', $tx));
    }

    public function test_cashier_can_request_and_void_with_otp(): void
    {
        $tx = $this->createTransaction($this->cashier, '123450');
        $this->assertNotNull($tx);

        $this->requestVoid($tx);
        $otp = $this->approve($tx);
        $this->executeVoid($tx, $otp);

        $this->assertDatabaseHas('transactions', ['id' => $tx->id, 'status' => 'VOID']);
        $this->assertDatabaseHas('shift_activities', ['activity_type' => 'TRANSACTION_VOID']);
        $this->assertDatabaseHas('transaction_authorizations', [
            'transaction_id' => $tx->id,
            'status' => 'USED',
        ]);
    }

    public function test_void_rejects_already_voided(): void
    {
        $tx = $this->createTransaction($this->cashier, '123450');
        $tx->update(['status' => Transaction::STATUS_VOID]);

        $this->post(route('transactions.void', $tx), ['otp' => '111111'])
            ->assertSessionHasErrors('void');
    }

    public function test_request_requires_valid_transaction_code(): void
    {
        $tx = $this->createTransaction($this->cashier, '123450');

        $this->post(route('transactions.authorize', $tx), [
            'action' => 'VOID',
            'transaction_code' => 'SALAH',
            'reason' => 'x',
        ])->assertSessionHasErrors('authorize');

        $this->assertDatabaseMissing('transaction_authorizations', [
            'transaction_id' => $tx->id,
            'status' => 'PENDING',
        ]);
    }

    public function test_request_requires_reason(): void
    {
        $tx = $this->createTransaction($this->cashier, '123450');

        $this->post(route('transactions.authorize', $tx), [
            'action' => 'VOID',
            'transaction_code' => $tx->transaction_number,
        ])->assertSessionHasErrors('reason');
    }

    public function test_void_rejects_wrong_otp(): void
    {
        $tx = $this->createTransaction($this->cashier, '123450');
        $this->requestVoid($tx);
        $this->approve($tx);

        $this->executeVoid($tx, '000000');

        $this->assertDatabaseHas('transactions', ['id' => $tx->id, 'status' => 'COMPLETED']);
    }

    public function test_void_rejects_expired_otp(): void
    {
        $tx = $this->createTransaction($this->cashier, '123450');
        $this->requestVoid($tx);
        $otp = $this->approve($tx);

        TransactionAuthorization::where('transaction_id', $tx->id)
            ->update(['otp_expires_at' => now()->subMinute()]);

        $this->executeVoid($tx, $otp);

        $this->assertDatabaseHas('transactions', ['id' => $tx->id, 'status' => 'COMPLETED']);
    }

    public function test_otp_is_single_use(): void
    {
        $tx = $this->createTransaction($this->cashier, '123450');
        $this->requestVoid($tx);
        $otp = $this->approve($tx);
        $this->executeVoid($tx, $otp);

        $this->post(route('transactions.void', $tx), ['otp' => $otp])
            ->assertSessionHasErrors('void');

        $this->assertCount(1, Transaction::where('id', $tx->id)->where('status', 'VOID')->get());
    }
}
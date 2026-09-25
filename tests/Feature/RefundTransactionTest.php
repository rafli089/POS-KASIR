<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionAuthorization;
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

    private function requestRefund(int $amount = 36000, string $reason = 'pelanggan batal beli'): TransactionAuthorization
    {
        $tx = Transaction::first();

        $this->post(route('transactions.authorize', $tx), [
            'action' => 'REFUND',
            'transaction_code' => $tx->transaction_number,
            'reason' => $reason,
            'amount' => $amount,
        ])->assertRedirect();

        return TransactionAuthorization::where('transaction_id', $tx->id)->where('action', 'REFUND')->firstOrFail();
    }

    private function approveAs(User $user, string $pin): string
    {
        $tx = Transaction::first();

        $this->post('/logout');
        $this->post('/login', ['user_id' => $user->id, 'pin' => $pin]);
        $this->session(['shift_id' => $tx->shift_id]);

        $this->post(route('transactions.approve', $tx))->assertRedirect();

        return $this->app['session']->get('authorization_otp');
    }

    public function test_cashier_cannot_refund_without_otp(): void
    {
        $tx = Transaction::first();
        $this->post(route('transactions.refund', $tx), ['otp' => '111111'])
            ->assertSessionHasErrors('refund');

        $this->assertSame('COMPLETED', $tx->fresh()->status);
    }

    public function test_refund_full_flow_with_otp(): void
    {
        $this->requestRefund();
        $otp = $this->approveAs($this->manager, '123456');

        $tx = Transaction::first();
        $this->post('/logout');
        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '123450']);
        $this->session(['shift_id' => $tx->shift_id]);

        $this->post(route('transactions.refund', $tx), ['otp' => $otp])
            ->assertRedirect(route('transactions.show', $tx));

        $this->assertSame('REFUNDED', $tx->fresh()->status);
        $this->assertDatabaseHas('shift_activities', [
            'activity_type' => 'TRANSACTION_REFUND',
            'reference_amount' => 36000,
        ]);
    }

    public function test_refund_rejects_already_refunded(): void
    {
        Transaction::first()->update(['status' => Transaction::STATUS_REFUNDED]);

        $tx = Transaction::first();
        $this->post(route('transactions.refund', $tx), ['otp' => '111111'])
            ->assertSessionHasErrors('refund');
    }

    public function test_refund_request_rejects_amount_greater_than_total(): void
    {
        $this->post(route('transactions.authorize', Transaction::first()), [
            'action' => 'REFUND',
            'transaction_code' => Transaction::first()->transaction_number,
            'reason' => 'x',
            'amount' => 999999,
        ])->assertSessionHasErrors('amount');
    }

    public function test_refund_excludes_from_expected_cash(): void
    {
        $shiftId = Transaction::first()->shift_id;

        $this->requestRefund();
        $otp = $this->approveAs($this->admin, '123456');

        $tx = Transaction::first();
        $this->post('/logout');
        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '123450']);
        $this->session(['shift_id' => $shiftId]);

        $this->post(route('transactions.refund', $tx), ['otp' => $otp])->assertRedirect();

        $this->get('/shift/current')->assertOk()->assertSee('464.000');
    }
}
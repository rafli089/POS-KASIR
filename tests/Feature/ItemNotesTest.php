<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemNotesTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::create(['name' => 'Rina', 'pin_hash' => '123450', 'role' => 'CASHIER']);

        $cat = Category::create(['name' => 'Kopi', 'sort_order' => 1]);
        $this->product = Product::create(['category_id' => $cat->id, 'name' => 'Kopi Susu', 'sku' => 'KS', 'price' => 24000, 'status' => 'ACTIVE']);
        $this->cash = PaymentMethod::create(['name' => 'Cash', 'code' => 'CASH', 'status' => 'ACTIVE']);

        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '123450']);
        $this->post('/shift/open', ['opening_cash' => 500000]);
    }

    public function test_transaction_saves_item_notes(): void
    {
        $this->postJson('/transactions', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 2, 'notes' => 'less sugar']],
            'discount' => 0, 'tax' => 0,
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 50000,
        ])->assertStatus(201);

        $item = TransactionItem::first();
        $this->assertSame('less sugar', $item->notes);
    }

    public function test_notes_visible_on_transaction_detail(): void
    {
        $this->postJson('/transactions', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'notes' => 'extra hot']],
            'discount' => 0, 'tax' => 0,
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 25000,
        ]);

        $txId = \App\Models\Transaction::first()->id;
        $this->get("/transactions/{$txId}")
            ->assertOk()
            ->assertSee('extra hot');
    }

    public function test_notes_optional(): void
    {
        $this->postJson('/transactions', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'discount' => 0, 'tax' => 0,
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 25000,
        ])->assertStatus(201);

        $this->assertNull(TransactionItem::first()->notes);
    }
}
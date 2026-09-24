<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModifierSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private User $admin;
    private Product $product;
    private Product $plain;
    private PaymentMethod $cash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::create(['name' => 'Rina', 'pin_hash' => '123450', 'role' => 'CASHIER']);
        $this->admin = User::create(['name' => 'Admin', 'pin_hash' => '123456', 'role' => 'ADMIN']);

        $cat = Category::create(['name' => 'Kopi', 'sort_order' => 1]);
        $this->product = Product::create(['category_id' => $cat->id, 'name' => 'Cafe Latte', 'sku' => 'LAT', 'price' => 25000, 'status' => 'ACTIVE']);
        $this->plain = Product::create(['category_id' => $cat->id, 'name' => 'Roti Bakar', 'sku' => 'RB', 'price' => 15000, 'status' => 'ACTIVE']);

        $size = ModifierGroup::create(['name' => 'Ukuran', 'required' => true]);
        Modifier::create(['modifier_group_id' => $size->id, 'name' => 'Small', 'price_modifier' => 0]);
        Modifier::create(['modifier_group_id' => $size->id, 'name' => 'Large', 'price_modifier' => 6000]);
        $extra = ModifierGroup::create(['name' => 'Ekstra', 'required' => false]);
        Modifier::create(['modifier_group_id' => $extra->id, 'name' => 'Extra Shot', 'price_modifier' => 7000]);

        $this->product->modifierGroups()->attach([$size->id, $extra->id]);

        $this->cash = PaymentMethod::create(['name' => 'Cash', 'code' => 'CASH', 'status' => 'ACTIVE']);

        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '123450']);
        $this->post('/shift/open', ['opening_cash' => 500000]);
    }

    public function test_store_includes_modifier_price_and_json(): void
    {
        $this->postJson('/transactions', [
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => 2,
                'modifiers' => [
                    ['group' => 'Ukuran', 'name' => 'Large', 'price_modifier' => 6000],
                    ['group' => 'Ekstra', 'name' => 'Extra Shot', 'price_modifier' => 7000],
                ],
            ]],
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 76000, // (25000+13000)*2
        ])->assertStatus(201);

        $tx = Transaction::first();
        $this->assertSame(76000, $tx->grand_total);
        $this->assertSame(38000, $tx->items->first()->unit_price);
        $this->assertSame('Ukuran', $tx->items->first()->modifiers[0]['group']);
        $this->assertSame('Large', $tx->items->first()->modifiers[0]['name']);
        $this->assertSame(6000, $tx->items->first()->modifiers[0]['price_modifier']);
        $this->assertSame('Extra Shot', $tx->items->first()->modifiers[1]['name']);
    }

    public function test_plain_product_store_without_modifiers(): void
    {
        $this->postJson('/transactions', [
            'items' => [['product_id' => $this->plain->id, 'quantity' => 1]],
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 15000,
        ])->assertStatus(201);

        $this->assertNull(Transaction::first()->items->first()->modifiers);
    }

    public function test_invalid_modifier_price_rejected(): void
    {
        $this->postJson('/transactions', [
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => 1,
                'modifiers' => [['group' => 'Ukuran', 'name' => 'Large', 'price_modifier' => -1]],
            ]],
            'payment_method_id' => $this->cash->id,
            'payment_amount' => 31000,
        ])->assertStatus(422);
    }

    public function test_admin_assigns_modifier_groups(): void
    {
        $this->post('/logout');
        $this->post('/login', ['user_id' => $this->admin->id, 'pin' => '123456']);

        $group = ModifierGroup::create(['name' => 'Porsi']);

        $this->post(route('modifiers.update'), [
            'groups' => [$this->plain->id => [$group->id]],
        ])->assertRedirect(route('modifiers.index'));

        $this->assertTrue($this->plain->fresh()->modifierGroups->contains('id', $group->id));
    }

    public function test_modifier_config_page_lists_groups(): void
    {
        $this->post('/logout');
        $this->post('/login', ['user_id' => $this->admin->id, 'pin' => '123456']);

        $this->get(route('modifiers.index'))
            ->assertOk()
            ->assertSee('Ukuran')
            ->assertSee('Ekstra')
            ->assertSee('Cafe Latte');
    }
}
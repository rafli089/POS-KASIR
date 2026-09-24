<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cashier;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create(['name' => 'Admin', 'pin_hash' => '123456', 'role' => 'ADMIN']);
        $this->cashier = User::create(['name' => 'Rina', 'pin_hash' => '123450', 'role' => 'CASHIER']);

        $this->category = Category::create(['name' => 'Kopi', 'sort_order' => 1]);
    }

    private function loginAdmin(): void
    {
        $this->post('/login', ['user_id' => $this->admin->id, 'pin' => '123456']);
    }

    private function loginCashier(): void
    {
        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '123450']);
    }

    public function test_cashier_cannot_access_products(): void
    {
        $this->loginCashier();
        $this->get(route('products.index'))->assertRedirect(route('pos.index'));
    }

    public function test_admin_can_list_products(): void
    {
        Product::create([
            'category_id' => $this->category->id,
            'name' => 'Espresso',
            'sku' => 'ESP',
            'price' => 18000,
            'status' => 'ACTIVE',
        ]);

        $this->loginAdmin();
        $this->get(route('products.index'))->assertOk()->assertSee('Espresso');
    }

    public function test_admin_can_create_product(): void
    {
        $this->loginAdmin();
        $this->post(route('products.store'), [
            'category_id' => $this->category->id,
            'name' => 'Cappuccino',
            'sku' => 'CAP',
            'price' => 25000,
            'cost_price' => 10000,
            'status' => 'ACTIVE',
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['sku' => 'CAP', 'price' => 25000]);
    }

    public function test_admin_can_update_product(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Espresso',
            'sku' => 'ESP',
            'price' => 18000,
            'status' => 'ACTIVE',
        ]);

        $this->loginAdmin();
        $this->put(route('products.update', $product), [
            'category_id' => $this->category->id,
            'name' => 'Espresso Doppio',
            'sku' => 'ESP',
            'price' => 22000,
            'cost_price' => 8000,
            'status' => 'ACTIVE',
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Espresso Doppio', 'price' => 22000]);
    }

    public function test_admin_can_delete_product(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Espresso',
            'sku' => 'ESP',
            'price' => 18000,
            'status' => 'ACTIVE',
        ]);

        $this->loginAdmin();
        $this->delete(route('products.destroy', $product))->assertRedirect(route('products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_validation_rejects_duplicate_sku(): void
    {
        Product::create([
            'category_id' => $this->category->id,
            'name' => 'Espresso',
            'sku' => 'ESP',
            'price' => 18000,
            'status' => 'ACTIVE',
        ]);

        $this->loginAdmin();
        $this->post(route('products.store'), [
            'category_id' => $this->category->id,
            'name' => 'Espresso Double',
            'sku' => 'ESP',
            'price' => 22000,
            'status' => 'ACTIVE',
        ])->assertSessionHasErrors('sku');
    }
}
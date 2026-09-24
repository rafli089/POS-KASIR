<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create(['name' => 'Admin', 'pin_hash' => '123456', 'role' => 'ADMIN']);
        $this->cashier = User::create(['name' => 'Rina', 'pin_hash' => '123450', 'role' => 'CASHIER']);

        $cat = Category::create(['name' => 'Kopi', 'sort_order' => 1]);
        $product = Product::create(['category_id' => $cat->id, 'name' => 'Espresso', 'sku' => 'ESP', 'price' => 18000, 'status' => 'ACTIVE']);
        $cash = PaymentMethod::create(['name' => 'Cash', 'code' => 'CASH', 'status' => 'ACTIVE']);

        $this->post('/login', ['user_id' => $this->cashier->id, 'pin' => '123450']);
        $this->post('/shift/open', ['opening_cash' => 500000]);
        $this->postJson('/transactions', [
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
            'discount' => 0, 'tax' => 0,
            'payment_method_id' => $cash->id,
            'payment_amount' => 54000,
        ]);
    }

    public function test_cashier_cannot_access_reports(): void
    {
        $this->get(route('reports.daily'))->assertRedirect(route('pos.index'));
        $this->get(route('reports.products'))->assertRedirect(route('pos.index'));
    }

    public function test_admin_sees_daily_report(): void
    {
        $this->post('/login', ['user_id' => $this->admin->id, 'pin' => '123456']);
        $this->get(route('reports.daily'))->assertOk()->assertSee('54.000');
    }

    public function test_admin_sees_product_sales(): void
    {
        $this->post('/login', ['user_id' => $this->admin->id, 'pin' => '123456']);
        $this->get(route('reports.products'))->assertOk()->assertSee('54.000');
    }

    public function test_export_daily_csv(): void
    {
        $this->post('/login', ['user_id' => $this->admin->id, 'pin' => '123456']);

        $response = $this->get(route('reports.export', ['type' => 'daily']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertDownload('report-daily-'.date('Y-m-01').'-'.date('Y-m-d').'.csv');
        $this->assertStringContainsString('Tanggal,Transaksi,Pendapatan', $response->streamedContent());
        $this->assertStringContainsString('54', $response->streamedContent());
    }

    public function test_export_products_csv(): void
    {
        $this->post('/login', ['user_id' => $this->admin->id, 'pin' => '123456']);

        $response = $this->get(route('reports.export', ['type' => 'products']));

        $response->assertOk();
        $this->assertStringContainsString('Produk,Terjual,Pendapatan', $response->streamedContent());
        $this->assertStringContainsString('Espresso', $response->streamedContent());
    }

    public function test_print_daily_report(): void
    {
        $this->post('/login', ['user_id' => $this->admin->id, 'pin' => '123456']);

        $this->get(route('reports.print', ['type' => 'daily']))
            ->assertOk()
            ->assertSee('Laporan Harian');
    }

    public function test_print_product_report(): void
    {
        $this->post('/login', ['user_id' => $this->admin->id, 'pin' => '123456']);

        $this->get(route('reports.print', ['type' => 'products']))
            ->assertOk()
            ->assertSee('Laporan Penjualan Produk')
            ->assertSee('Espresso');
    }
}
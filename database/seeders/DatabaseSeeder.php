<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'pin_hash' => '123456',
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
        ]);

        $manager = User::create([
            'name' => 'Manager',
            'pin_hash' => '123456',
            'role' => 'MANAGER',
            'status' => 'ACTIVE',
        ]);

        $cashiers = ['Rina', 'Budi', 'Sari'];
        foreach ($cashiers as $i => $name) {
            User::create([
                'name' => $name,
                'pin_hash' => '12345' . $i, // 123450, 123451, 123452
                'role' => 'CASHIER',
                'status' => 'ACTIVE',
            ]);
        }

        $categories = [
            ['name' => 'Kopi', 'sort_order' => 1],
            ['name' => 'Non-Kopi', 'sort_order' => 2],
            ['name' => 'Makanan', 'sort_order' => 3],
            ['name' => 'Camilan', 'sort_order' => 4],
        ];

        $categoryIds = [];
        foreach ($categories as $cat) {
            $categoryIds[] = Category::create($cat)->id;
        }

        $products = [
            ['Kopi', 'ESPRESSO', 'Espresso', 18000, 8000],
            ['Kopi', 'CAPPUCCINO', 'Cappuccino', 25000, 10000],
            ['Kopi', 'LATTE', 'Cafe Latte', 26000, 11000],
            ['Kopi', 'AMERICANO', 'Americano', 20000, 8000],
            ['Kopi', 'KOPI_SUSU', 'Kopi Susu Gula Aren', 24000, 10000],
            ['Non-Kopi', 'CHOCO', 'Chocolate', 22000, 9000],
            ['Non-Kopi', 'MATCHA', 'Matcha Latte', 27000, 12000],
            ['Non-Kopi', 'TEH_TARIK', 'Teh Tarik', 18000, 7000],
            ['Makanan', 'NASGOR_SPEC', 'Nasi Goreng Spesial', 30000, 15000],
            ['Makanan', 'MIE_GORENG', 'Mie Goreng', 28000, 13000],
            ['Makanan', 'SPAGHETTI', 'Spaghetti Bolognese', 32000, 16000],
            ['Camilan', 'ROTI_BAKAR', 'Roti Bakar Coklat', 15000, 6000],
            ['Camilan', 'PISANG_KEJU', 'Pisang Goreng Keju', 12000, 5000],
        ];

        // Map category name → id
        $catIdByName = [];
        foreach ($categories as $cat) {
            $catIdByName[$cat['name']] = Category::where('name', $cat['name'])->first()->id;
        }

        foreach ($products as $p) {
            Product::create([
                'category_id' => $catIdByName[$p[0]],
                'name' => $p[2],
                'sku' => $p[1],
                'price' => $p[3],
                'cost_price' => $p[4],
                'status' => 'ACTIVE',
            ]);
        }

        $paymentMethods = [
            ['name' => 'Cash', 'code' => 'CASH'],
            ['name' => 'QRIS', 'code' => 'QRIS'],
            ['name' => 'Debit', 'code' => 'DEBIT'],
            ['name' => 'Credit Card', 'code' => 'CREDIT'],
        ];

        foreach ($paymentMethods as $pm) {
            PaymentMethod::create($pm);
        }

        $this->call(ModifierSeeder::class);
    }
}
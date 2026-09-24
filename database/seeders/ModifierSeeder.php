<?php

namespace Database\Seeders;

use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ModifierSeeder extends Seeder
{
    public function run(): void
    {
        $size = ModifierGroup::create(['name' => 'Ukuran', 'required' => true, 'display_order' => 1]);
        Modifier::create(['modifier_group_id' => $size->id, 'name' => 'Small', 'price_modifier' => 0, 'display_order' => 1]);
        Modifier::create(['modifier_group_id' => $size->id, 'name' => 'Medium', 'price_modifier' => 3000, 'display_order' => 2]);
        Modifier::create(['modifier_group_id' => $size->id, 'name' => 'Large', 'price_modifier' => 6000, 'display_order' => 3]);

        $extra = ModifierGroup::create(['name' => 'Ekstra', 'required' => false, 'display_order' => 2]);
        Modifier::create(['modifier_group_id' => $extra->id, 'name' => 'Extra Shot', 'price_modifier' => 7000, 'display_order' => 1]);
        Modifier::create(['modifier_group_id' => $extra->id, 'name' => 'Less Sugar', 'price_modifier' => 0, 'display_order' => 2]);
        Modifier::create(['modifier_group_id' => $extra->id, 'name' => 'No Sugar', 'price_modifier' => 0, 'display_order' => 3]);

        // Minuman (Kopi & Non-Kopi) mendapat grup Ukuran + Ekstra
        Product::whereHas('category', fn ($q) => $q->whereIn('name', ['Kopi', 'Non-Kopi']))
            ->each(function (Product $product) use ($size, $extra) {
                $product->modifierGroups()->attach([
                    $size->id  => ['display_order' => 1],
                    $extra->id => ['display_order' => 2],
                ]);
            });
    }
}
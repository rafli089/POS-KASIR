<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::select('SELECT MIN(id) AS keep, GROUP_CONCAT(id) AS ids FROM categories GROUP BY name HAVING COUNT(*) > 1');
        foreach ($duplicates as $row) {
            $ids = explode(',', $row->ids);
            $keep = array_shift($ids);
            foreach ($ids as $dupId) {
                DB::table('products')->where('category_id', $dupId)->update(['category_id' => $keep]);
                DB::table('categories')->where('id', $dupId)->delete();
            }
        }
    }

    public function down(): void {}
};
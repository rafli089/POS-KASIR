<?php
// temp script: cek duplikat kategori
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows = \Illuminate\Support\Facades\DB::table('categories')->selectRaw('name, COUNT(*) c, MIN(id) keep')->groupBy('name')->havingRaw('COUNT(*) > 1')->get();
echo $rows->toJson(JSON_PRETTY_PRINT), PHP_EOL;
$cats = \Illuminate\Support\Facades\DB::table('categories')->orderBy('id')->get(['id','name','sort_order']);
echo $cats->toJson(JSON_PRETTY_PRINT), PHP_EOL;
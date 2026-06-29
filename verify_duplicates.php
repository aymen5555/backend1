<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$all = array_merge(
    App\Models\Complexe::all()->pluck('image_c')->filter()->toArray(),
    App\Models\Terrain::all()->pluck('image_t')->filter()->toArray(),
    App\Models\Produit::all()->pluck('image')->filter()->toArray()
);
$counts = array_count_values($all);
$dups = array_filter($counts, fn($c) => $c > 1);
echo "Total: " . count($all) . " | Unique: " . (count($counts)) . " | Duplicates: " . count($dups) . "\n";
foreach ($dups as $url => $cnt) echo "$cnt x $url\n";
?>
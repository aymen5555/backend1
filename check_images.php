<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Complexes:\n";
foreach (\App\Models\Complexe::all() as $c) {
    echo $c->id . ' - ' . $c->image_c . "\n";
}
echo "\nTerrains:\n";
foreach (\App\Models\Terrain::all() as $t) {
    echo $t->id . ' - ' . $t->name . ' - ' . $t->image_t . "\n";
}

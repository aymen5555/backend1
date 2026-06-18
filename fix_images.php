<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$brokenUrls = [
    'https://images.unsplash.com/photo-1612534847738-b3af9bc31f0e?w=800',
    'https://images.unsplash.com/photo-1612534847738-b3af9bc31f0e?w=1200',
    'https://images.unsplash.com/photo-1545809074-59472b3f5ecc?w=800'
];

$replacementPadel = 'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=800'; // known working court padel A
$replacementTennis = 'https://images.unsplash.com/photo-1622279457486-62dcc4a431d6?w=800'; // known working court tennis A

foreach (\App\Models\Terrain::all() as $t) {
    if ($t->image_t === 'https://images.unsplash.com/photo-1612534847738-b3af9bc31f0e?w=800') {
        $t->image_t = $replacementPadel;
        $t->save();
        echo "Updated terrain {$t->id} image\n";
    }
    if ($t->image_t === 'https://images.unsplash.com/photo-1545809074-59472b3f5ecc?w=800') {
        $t->image_t = $replacementTennis;
        $t->save();
        echo "Updated terrain {$t->id} image\n";
    }
}

foreach (\App\Models\Complexe::all() as $c) {
    if ($c->image_c === 'https://images.unsplash.com/photo-1612534847738-b3af9bc31f0e?w=1200') {
        $c->image_c = 'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=1200';
        $c->save();
        echo "Updated complexe {$c->id} image\n";
    }
}
echo "Done replacing images.\n";

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$complexes = \App\Models\Complexe::all();
foreach ($complexes as $complexe) {
    $updated = false;
    foreach ($complexe->getAttributes() as $key => $value) {
        if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
            $complexe->{$key} = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            if (!mb_check_encoding($complexe->{$key}, 'UTF-8')) {
                $complexe->{$key} = utf8_encode($value);
            }
            $updated = true;
            echo "Fixed complexe ID {$complexe->id} column {$key}\n";
        }
    }
    if ($updated) {
        $complexe->save();
    }
}

$terrains = \App\Models\Terrain::all();
foreach ($terrains as $terrain) {
    $updated = false;
    foreach ($terrain->getAttributes() as $key => $value) {
        if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
            $terrain->{$key} = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            if (!mb_check_encoding($terrain->{$key}, 'UTF-8')) {
                $terrain->{$key} = utf8_encode($value);
            }
            $updated = true;
            echo "Fixed terrain ID {$terrain->id} column {$key}\n";
        }
    }
    if ($updated) {
        $terrain->save();
    }
}

echo "Done\n";

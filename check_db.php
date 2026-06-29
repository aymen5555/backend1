<?php
require_once 'bootstrap/app.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$terrains = DB::table('terrains')->where('sport_type', 'padel')->get(['name', 'image_t']);
foreach ($terrains as $t) {
    $id = substr($t->image_t, strpos($t->image_t, 'photos/') + 7, 10);
    echo $t->name . ' => ' . $id . PHP_EOL;
}

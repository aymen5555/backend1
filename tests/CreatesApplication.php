<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        // Run migrations for in-memory SQLite
        $app->make(Kernel::class)->call('migrate', ['--force' => true]);

        return $app;
    }
}

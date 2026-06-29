<?php

namespace App\Console\Commands;

use Illuminate\Database\Console\Migrations\FreshCommand;

class SafeMigrateFreshCommand extends FreshCommand
{
    public function confirmToProceed($warning = 'Application is in production!', $callback = null): bool
    {
        if ($this->option('force') && env('ALLOW_MIGRATE_FRESH')) {
            return true;
        }

        $this->components->error('⛔  migrate:fresh is BLOCKED.');
        $this->components->warn('This command drops ALL tables and deletes all data.');
        $this->components->warn('To allow it, run:  ALLOW_MIGRATE_FRESH=true php artisan migrate:fresh --force');
        $this->components->warn('Or set ALLOW_MIGRATE_FRESH=true in your .env file.');

        return false;
    }
}

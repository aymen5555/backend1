<?php

namespace App\Console\Commands;

use App\Models\ReservationActivite;
use Illuminate\Console\Command;

class UpdateActiviteStatus extends Command
{
    protected $signature = 'activites:update-status';

    protected $description = 'Update expired activity reservation statuses';

    public function handle(): void
    {
        ReservationActivite::updateExpiredStatus();
        $this->info('Activity statuses updated.');
    }
}

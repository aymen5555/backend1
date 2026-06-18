<?php

namespace App\Console\Commands;

use App\Models\AbonnementAdherent;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateAbonnementsAdherentStatus extends Command
{
    protected $signature = 'abonnements:update-status';
    protected $description = 'Mark expired adhérent subscriptions as expired (statut=expire)';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        $count = AbonnementAdherent::where('statut', 'actif')
            ->where('date_fin', '<', $today)
            ->update(['statut' => 'expire']);

        $this->info("Updated {$count} expired adhérent subscription(s).");
        return Command::SUCCESS;
    }
}

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

        $expiringAbonnements = AbonnementAdherent::with(['user', 'complexe', 'typeAbonnement'])
            ->where('statut', 'actif')
            ->where('date_fin', '<', $today)
            ->get();

        $count = 0;
        foreach ($expiringAbonnements as $sub) {
            $sub->update(['statut' => 'expire']);
            $count++;
            if ($sub->user) {
                $sub->user->notify(new \App\Notifications\AbonnementStatusChanged($sub));
            }
        }

        $this->info("Updated {$count} expired adhérent subscription(s).");

        return Command::SUCCESS;
    }
}

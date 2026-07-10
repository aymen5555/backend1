<?php

namespace App\Console\Commands;

use App\Models\AbonnementAdherent;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupInvalidAbonnementsCommand extends Command
{
    protected $signature = 'abonnements:cleanup-invalid';

    protected $description = 'Archive invalid subscriptions that should never affect eligibility';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        // Only archive subscriptions that have already expired.
        // We intentionally avoid archiving unpaid-but-active (cash) subscriptions.
        $invalidSubscriptions = AbonnementAdherent::query()
            ->where('statut', 'actif')
            ->whereDate('date_fin', '<', $today)
            ->get();

        $count = 0;
        foreach ($invalidSubscriptions as $subscription) {
            $subscription->delete();
            $count++;
        }

        $this->info("Archived {$count} invalid abonnement(s).");

        return Command::SUCCESS;
    }
}

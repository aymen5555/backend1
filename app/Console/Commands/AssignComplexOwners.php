<?php

namespace App\Console\Commands;

use App\Models\Complexe;
use App\Models\User;
use Illuminate\Console\Command;

class AssignComplexOwners extends Command
{
    protected $signature = 'complexes:assign-owner {--user-id=} {--email=} {--dry-run} {--force}';

    protected $description = 'Assign owner_id for complexes that have null owner_id. Provide --user-id or --email, or the command will pick a sensible default.';

    public function handle(): int
    {
        $userId = $this->option('user-id');
        $email = $this->option('email');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $user = null;

        if ($email) {
            $user = User::where('email', strtolower($email))->first();
            if (! $user) {
                $this->error("No user found with email: {$email}");
                return 1;
            }
        } elseif ($userId) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("No user found with id: {$userId}");
                return 1;
            }
        } else {
            // Prefer a super_admin, then admin, then first user
            $user = User::where('role', 'super_admin')->first()
                ?? User::where('role', 'admin')->first()
                ?? User::first();

            if (! $user) {
                $this->error('No users exist in the database to assign as owner. Create a user first.');
                return 1;
            }

            $this->info("No user specified; defaulting to user id={$user->id}, email={$user->email}, role={$user->role}");
        }

        $complexes = Complexe::whereNull('owner_id')->get();

        if ($complexes->isEmpty()) {
            $this->info('No complexes with null owner_id found.');
            return 0;
        }

        $this->info('Found ' . $complexes->count() . ' complexes with null owner_id.');

        foreach ($complexes as $c) {
            $this->line("- [{$c->id}] {$c->name} (created_at: {$c->created_at})");
        }

        if ($dryRun) {
            $this->info('Dry-run mode: no changes applied.');
            return 0;
        }

        if (! $force) {
            if (! $this->confirm('Proceed to assign owner_id=' . $user->id . ' to these complexes?')) {
                $this->info('Aborted.');
                return 0;
            }
        }

        $count = 0;
        foreach ($complexes as $c) {
            $c->owner_id = $user->id;
            $c->save();
            $count++;
        }

        $this->info("Assigned owner_id={$user->id} to {$count} complexes.");

        return 0;
    }
}

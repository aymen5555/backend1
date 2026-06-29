<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateReservationStatus extends Command
{
    protected $signature = 'reservations:update-status';

    protected $description = 'Update expired and played reservation statuses';

    public function handle(): int
    {
        $now = Carbon::now('Africa/Tunis');

        $expiredReservations = Reservation::where('status', 'pending')
            ->where('start_at', '<', $now)
            ->get();
        
        $expiredCount = 0;
        foreach ($expiredReservations as $reservation) {
            $reservation->update(['status' => 'expired']);
            $expiredCount++;
            if ($reservation->user) {
                $reservation->user->notify(
                    new \App\Notifications\ReservationStatusChanged($reservation)
                );
            }
        }

        $playedReservations = Reservation::where('status', 'confirmed')
            ->where('start_at', '<', $now)
            ->get();

        $playedCount = 0;
        foreach ($playedReservations as $reservation) {
            $reservation->update(['status' => 'played']);
            $playedCount++;
            if ($reservation->user) {
                $reservation->user->notify(
                    new \App\Notifications\ReservationStatusChanged($reservation)
                );
            }
        }

        $this->info("Updated {$expiredCount} reservations to expired and {$playedCount} to played.");

        return 0;
    }
}

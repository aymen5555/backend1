<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateReservationStatus extends Command
{
    protected $signature = "reservations:update-status";
    protected $description = "Update expired and played reservation statuses";

    public function handle(): int
    {
        $now = Carbon::now("Africa/Tunis");
        
        $expired = Reservation::where("status", "pending")
            ->where("start_at", "<", $now)
            ->update(["status" => "expired"]);
        
        $played = Reservation::where("status", "confirmed")
            ->where("start_at", "<", $now)
            ->update(["status" => "played"]);
        
        $this->info("Updated {$expired} reservations to expired and {$played} to played.");
        
        return 0;
    }
}

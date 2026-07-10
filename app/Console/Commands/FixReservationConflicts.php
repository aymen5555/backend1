<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\ReservationActivite;
use App\Models\Activite;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixReservationConflicts extends Command
{
    protected $signature = 'reservations:fix-conflicts {--apply : Apply fixes (default is dry-run)}';

    protected $description = 'Detect and optionally fix overlapping reservations for the same user (court/activity).';

    public function handle(): int
    {
        $apply = $this->option('apply');

        $this->info('Scanning for reservation conflicts...');

        $courtConflicts = $this->findCourtConflicts();
        $activityConflicts = $this->findActivityConflicts();
        $crossConflicts = $this->findCourtActivityConflicts();

        $this->line('');
        $this->info("Court vs Court conflicts: " . count($courtConflicts));
        $this->info("Activity vs Activity conflicts: " . count($activityConflicts));
        $this->info("Court vs Activity conflicts: " . count($crossConflicts));

        if (! $apply) {
            $this->line('Running in dry-run mode. Use --apply to make changes.');
            // show samples
            $this->showSamples($courtConflicts, 'court');
            $this->showSamples($activityConflicts, 'activity');
            $this->showSamples($crossConflicts, 'cross');
            return 0;
        }

        // Apply fixes: for each set of conflicting ids for a user, cancel duplicates keeping preferred one
        DB::transaction(function () use ($courtConflicts, $activityConflicts, $crossConflicts) {
            $toCancel = [];

            // Process court conflicts
            foreach ($courtConflicts as $pair) {
                $keep = $this->chooseKeepReservation($pair['r1'], $pair['r2']);
                $other = ($keep['id'] === $pair['r1']['id']) ? $pair['r2'] : $pair['r1'];
                $toCancel[] = $other['id'];
            }

            // Activity conflicts
            foreach ($activityConflicts as $pair) {
                $keep = $this->chooseKeepActivityReservation($pair['a1'], $pair['a2']);
                $other = ($keep['id'] === $pair['a1']['id']) ? $pair['a2'] : $pair['a1'];
                $toCancel[] = $other['id'];
            }

            // Cross conflicts: choose between reservation and activity
            foreach ($crossConflicts as $pair) {
                // prefer confirmed reservation/activity, otherwise keep earliest start
                $r = $pair['r'];
                $a = $pair['a'];
                $keepId = null;
                if ($r['status'] === 'confirmed' || $r['status'] === 'confirmed') {
                    $keepId = $r['id'];
                } elseif ($a['statut'] === 'confirmee') {
                    $keepId = $a['id'];
                } else {
                    // compare start time
                    $rStart = Carbon::parse($r['start_at']);
                    $aStart = Carbon::parse($a['start_at']);
                    $keepId = $rStart->lte($aStart) ? $r['id'] : $a['id'];
                }
                $otherId = ($keepId === $r['id']) ? $a['id'] : $r['id'];
                // note: activity ids live in reservation_activites table
                $toCancel[] = $otherId;
            }

            $toCancel = array_unique($toCancel);
            if (count($toCancel) === 0) {
                $this->info('No reservations to cancel.');
                return;
            }

            // Cancel activity reservations (ids present in reservation_activites) vs court reservations
            // We will try updating both tables accordingly
            $cancelRes = array_filter($toCancel, fn($id) => is_int($id));

            // For reservation_activites, check existence and avoid re-marking already-updated rows
            $raIds = ReservationActivite::whereIn('id', $cancelRes)
                ->where('notes', 'not like', '%cancelled by conflict-fix%')
                ->pluck('id')
                ->toArray();
            if (! empty($raIds)) {
                ReservationActivite::whereIn('id', $raIds)->update([
                    'statut' => 'annulee',
                    'notes' => DB::raw("CONCAT(COALESCE(notes,''), ' | cancelled by conflict-fix')")
                ]);
            }

            // For reservations table, avoid re-marking
            $rIds = Reservation::whereIn('id', $cancelRes)
                ->where('notes', 'not like', '%cancelled by conflict-fix%')
                ->pluck('id')
                ->toArray();
            if (! empty($rIds)) {
                Reservation::whereIn('id', $rIds)->update([
                    'status' => 'cancelled',
                    'notes' => DB::raw("CONCAT(COALESCE(notes,''), ' | cancelled by conflict-fix')")
                ]);
            }

            $this->info('Applied cancellations to ' . (count($raIds) + count($rIds)) . ' reservations.');
        });

        return 0;
    }

    protected function findCourtConflicts(): array
    {
        $rows = DB::select(
            "SELECT r1.id as id1, r2.id as id2, r1.user_id, r1.terrain_id, r1.start_at as s1, r1.end_at as e1, r2.start_at as s2, r2.end_at as e2, r1.status as st1, r2.status as st2
            FROM reservations r1
            JOIN reservations r2 ON r1.user_id = r2.user_id AND r1.id < r2.id
            WHERE r1.start_at < r2.end_at
              AND r2.start_at < r1.end_at
              AND r1.status IN ('pending','confirmed')
              AND r2.status IN ('pending','confirmed')"
        );

        $out = [];
        foreach ($rows as $r) {
            $out[] = ['r1' => ['id' => (int)$r->id1, 'user_id' => (int)$r->user_id, 'terrain_id' => (int)$r->terrain_id, 'start_at' => $r->s1, 'end_at' => $r->e1, 'status' => $r->st1], 'r2' => ['id' => (int)$r->id2, 'start_at' => $r->s2, 'end_at' => $r->e2, 'status' => $r->st2]];
        }
        return $out;
    }

    protected function findActivityConflicts(): array
    {
        $rows = DB::select(
            "SELECT ra.id as id1, ra2.id as id2, ra.user_id, ra.activite_id, ra.date_seance as d1, ra2.date_seance as d2, a.heure_debut as h1, a.heure_fin as hh1, a2.heure_debut as h2, a2.heure_fin as hh2, ra.statut as st1, ra2.statut as st2
            FROM reservation_activites ra
            JOIN activites a ON a.id = ra.activite_id
            JOIN reservation_activites ra2 ON ra.user_id = ra2.user_id AND ra.id < ra2.id
            JOIN activites a2 ON a2.id = ra2.activite_id
            WHERE ra.statut IN ('reservee','confirmee')
              AND ra2.statut IN ('reservee','confirmee')"
        );

        $out = [];
        foreach ($rows as $r) {
            // compute start/end datetimes
            $s1 = $r->d1 . ' ' . ($r->h1 ?? '00:00:00');
            $e1 = $r->d1 . ' ' . ($r->hh1 ?? '23:59:59');
            $s2 = $r->d2 . ' ' . ($r->h2 ?? '00:00:00');
            $e2 = $r->d2 . ' ' . ($r->hh2 ?? '23:59:59');
            $out[] = ['a1' => ['id' => (int)$r->id1, 'user_id' => (int)$r->user_id, 'activite_id' => (int)$r->activite_id, 'start_at' => $s1, 'end_at' => $e1, 'statut' => $r->st1], 'a2' => ['id' => (int)$r->id2, 'start_at' => $s2, 'end_at' => $e2, 'statut' => $r->st2]];
        }
        return $out;
    }

    protected function findCourtActivityConflicts(): array
    {
        $rows = DB::select(
            "SELECT r.id as rid, r.user_id, r.start_at as rstart, r.end_at as rend, ra.id as aid, ra.date_seance as adate, a.heure_debut as ah, a.heure_fin as ahf, r.status as rstatus, ra.statut as astatus
            FROM reservations r
            JOIN reservation_activites ra ON r.user_id = ra.user_id
            JOIN activites a ON a.id = ra.activite_id
            WHERE r.status IN ('pending','confirmed')
              AND ra.statut IN ('reservee','confirmee')"
        );

        $out = [];
        foreach ($rows as $r) {
            $astart = $r->adate . ' ' . ($r->ah ?? '00:00:00');
            $aend = $r->adate . ' ' . ($r->ahf ?? '23:59:59');
            // check overlap
            if (Carbon::parse($r->rstart)->lt(Carbon::parse($aend)) && Carbon::parse($r->rend)->gt(Carbon::parse($astart))) {
                $out[] = ['r' => ['id' => (int)$r->rid, 'user_id' => (int)$r->user_id, 'start_at' => $r->rstart, 'end_at' => $r->rend, 'status' => $r->rstatus], 'a' => ['id' => (int)$r->aid, 'user_id' => (int)$r->user_id, 'start_at' => $astart, 'end_at' => $aend, 'statut' => $r->astatus]];
            }
        }
        return $out;
    }

    protected function showSamples(array $arr, string $type): void
    {
        if (count($arr) === 0) return;
        $this->line('--- Sample ' . $type . ' ---');
        $sample = array_slice($arr, 0, 5);
        foreach ($sample as $s) {
            $this->line(json_encode($s));
        }
    }

    protected function chooseKeepReservation(array $r1, array $r2): array
    {
        // prefer confirmed
        if (($r1['status'] ?? '') === 'confirmed' && ($r2['status'] ?? '') !== 'confirmed') return $r1;
        if (($r2['status'] ?? '') === 'confirmed' && ($r1['status'] ?? '') !== 'confirmed') return $r2;
        // else choose earliest start
        $s1 = Carbon::parse($r1['start_at']);
        $s2 = Carbon::parse($r2['start_at']);
        return $s1->lte($s2) ? $r1 : $r2;
    }

    protected function chooseKeepActivityReservation(array $a1, array $a2): array
    {
        if (($a1['statut'] ?? '') === 'confirmee' && ($a2['statut'] ?? '') !== 'confirmee') return $a1;
        if (($a2['statut'] ?? '') === 'confirmee' && ($a1['statut'] ?? '') !== 'confirmee') return $a2;
        $s1 = Carbon::parse($a1['start_at']);
        $s2 = Carbon::parse($a2['start_at']);
        return $s1->lte($s2) ? $a1 : $a2;
    }
}

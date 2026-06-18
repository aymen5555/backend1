<?php

namespace App\Services;

use App\Models\Complexe;
use App\Models\ProfilFitness;
use App\Models\RecommandationComplexe;
use App\Models\User;
use Illuminate\Support\Collection;

class RecommandationService
{
    /**
     * Generate top-3 complexe recommendations for a user.
     *
     * @return array{has_profile: bool, recommendations: array}
     */
    public function generate(User $user): array
    {
        $profil = ProfilFitness::where('user_id', $user->id)->first();

        if (!$profil) {
            // No profile: return top 3 by rating
            $topByRating = Complexe::where('is_active', true)
                ->orderByDesc('moyenne_notation_c')
                ->take(3)
                ->get();

            $recommendations = $topByRating->values()->map(function (Complexe $c, int $index) {
                return [
                    'rang'        => $index + 1,
                    'score'       => 0,
                    'complexe'    => $c->load('terrains'),
                    'explication' => 'Complétez votre profil fitness pour des recommandations personnalisées.',
                ];
            });

            return [
                'has_profile'    => false,
                'recommendations' => $recommendations->toArray(),
            ];
        }

        // Delete existing recommendations
        RecommandationComplexe::where('user_id', $user->id)->delete();

        $complexes = Complexe::with(['terrains'])
            ->where('is_active', true)
            ->get();

        $scored = collect();

        foreach ($complexes as $complexe) {
            $terrains = $complexe->terrains->where('is_active', true);
            if ($terrains->isEmpty()) {
                continue;
            }

            $score      = 0;
            $reasons    = [];

            // ── 40 pts: Sport match ──
            $sportMatch = $terrains->first(function ($t) use ($profil) {
                return strtolower(trim($t->sport_type)) === strtolower(trim($profil->sport_prefere));
            });
            if ($sportMatch) {
                $score += 40;
                $reasons[] = "Ce complexe propose du {$profil->sport_prefere}, votre sport préféré";
            }

            // ── 20 pts: Rating match (moyenne_notation_c > 4.0) ──
            if (($complexe->moyenne_notation_c ?? 0) > 4.0) {
                $score += 20;
                $reasons[] = 'avec une note de ' . number_format($complexe->moyenne_notation_c, 1) . '/5';
            }

            // ── 20 pts: Budget match ──
            // Budget mensuel max / 4 ≈ budget par semaine
            $budgetParSeance = $profil->budget_mensuel_max ? $profil->budget_mensuel_max / 4 : null;
            if ($budgetParSeance === null) {
                // No budget set → full points
                $score += 20;
            } else {
                $budgetMatch = $terrains->first(function ($t) use ($budgetParSeance) {
                    return (float) $t->price_per_hour <= $budgetParSeance;
                });
                if ($budgetMatch) {
                    $score += 20;
                    $reasons[] = 'et des tarifs adaptés à votre budget';
                }
            }

            // ── 20 pts: Active complexe ──
            if ($complexe->is_active) {
                $score += 20;
            }

            $scored->push([
                'complexe'    => $complexe,
                'score'       => $score,
                'explication' => $this->buildExplication($reasons, $complexe),
            ]);
        }

        // Sort descending by score
        $sorted = $scored->sortByDesc('score')->values();

        // Top 3
        $top3 = $sorted->take(3);

        // Persist
        $saved = [];
        foreach ($top3 as $rang => $item) {
            $rec = RecommandationComplexe::create([
                'user_id'     => $user->id,
                'complexe_id' => $item['complexe']->id,
                'score'       => $item['score'],
                'rang'        => $rang + 1,
                'explication' => $item['explication'],
            ]);
            $rec->setRelation('complexe', $item['complexe']);
            $saved[] = $rec;
        }

        return [
            'has_profile'    => true,
            'recommendations' => collect($saved)->map(function ($r) {
                return [
                    'rang'        => $r->rang,
                    'score'       => $r->score,
                    'complexe'    => $r->complexe,
                    'explication' => $r->explication,
                ];
            })->toArray(),
        ];
    }

    /**
     * Build a French explanation string from reason fragments.
     */
    private function buildExplication(array $reasons, Complexe $complexe): string
    {
        if (empty($reasons)) {
            return "{$complexe->name} est un complexe sportif recommandé pour vous.";
        }

        return implode(', ', $reasons) . '.';
    }
}

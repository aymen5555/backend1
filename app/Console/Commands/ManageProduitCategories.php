<?php

namespace App\Console\Commands;

use App\Models\CategorieProduit;
use App\Models\Produit;
use Illuminate\Console\Command;

class ManageProduitCategories extends Command
{
    protected $signature = 'produits:manage-categories {--assign=}';

    protected $description = 'List products missing categories, optionally assign a given category id to them.';

    public function handle(): int
    {
        $assign = $this->option('assign');

        $products = Produit::whereNull('categorie_id')->get(['id', 'nom']);

        if ($products->isEmpty()) {
            $this->info('No produits found without a categorie_id.');

            return 0;
        }

        $this->info("Found {$products->count()} produits without categorie_id:");
        $this->table(['id', 'nom'], $products->map(function ($p) {
            return [$p->id, $p->nom];
        })->toArray());

        if ($assign) {
            $cat = CategorieProduit::find($assign);
            if (! $cat) {
                $this->error("Category with id {$assign} not found.");

                return 2;
            }

            $this->info("Assigning category '{$cat->nom}' (id={$cat->id}) to all listed produits...");
            foreach ($products as $p) {
                $p->categorie_id = $cat->id;
                $p->save();
            }
            $this->info('Assignment complete.');
        } else {
            $this->info('Run this command with --assign=<category_id> to assign a category to all listed products.');
        }

        return 0;
    }
}

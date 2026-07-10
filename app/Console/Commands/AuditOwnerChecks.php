<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class AuditOwnerChecks extends Command
{
    protected $signature = 'audit:owner-checks {--path=app/Http/Controllers}';

    protected $description = 'Scan controller files for owner_id checks and report which files reference owner checks.';

    public function handle(Filesystem $files): int
    {
        $path = base_path($this->option('path'));

        if (! is_dir($path)) {
            $this->error("Path not found: {$path}");
            return 1;
        }

        $filesList = $files->allFiles($path);

        $report = [];

        foreach ($filesList as $f) {
            $content = $files->get($f->getRealPath());
            $hasOwner = str_contains($content, "owner_id") || str_contains($content, "where('owner_id") || str_contains($content, 'owner_id !==');
            $hasAuthorizeGerant = str_contains($content, 'authorizeGerant(') || str_contains($content, 'authorizeGerant ');
            $report[] = [
                'file' => str_replace(base_path() . '\\', '', $f->getRealPath()),
                'has_owner_check' => $hasOwner ? 'yes' : 'no',
                'has_authorizeGerant' => $hasAuthorizeGerant ? 'yes' : 'no',
            ];
        }

        $this->table(['file', 'has_owner_check', 'has_authorizeGerant'], $report);

        $noOwner = array_filter($report, fn($r)=> $r['has_owner_check'] === 'no');
        $this->info(count($noOwner) . ' files without explicit owner check.');

        return 0;
    }
}

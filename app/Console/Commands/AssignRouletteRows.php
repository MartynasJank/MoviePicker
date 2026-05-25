<?php

namespace App\Console\Commands;

use App\Models\Roulette;
use Illuminate\Console\Command;

class AssignRouletteRows extends Command
{
    protected $signature   = 'roulettes:assign-rows {--dry-run : Preview changes without saving}';
    protected $description = 'Assign the row field to roulettes that have none, based on their tags';

    private const PLATFORMS = [
        'netflix' => 'Netflix',
        'prime'   => 'Prime Video',
        'hbo'     => 'HBO',
        'disney'  => 'Disney+',
        'apple'   => 'Apple TV+',
    ];

    public function handle(): void
    {
        $roulettes = Roulette::whereNull('row')->orWhere('row', '')->get();

        if ($roulettes->isEmpty()) {
            $this->info('All roulettes already have a row assigned.');
            return;
        }

        $dryRun = $this->option('dry-run');
        $this->info(($dryRun ? '[dry-run] ' : '') . "Found {$roulettes->count()} roulettes without a row.");
        $this->newLine();

        $updated = 0;
        foreach ($roulettes as $roulette) {
            $row = $this->deriveRow($roulette);
            $this->line(sprintf('  %-45s → %s', $roulette->name, $row));

            if (!$dryRun) {
                $roulette->update(['row' => $row]);
                $updated++;
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info('Dry run complete — no changes saved. Run without --dry-run to apply.');
        } else {
            $this->info("Done. Assigned rows to {$updated} roulettes.");
        }
    }

    private function deriveRow(Roulette $roulette): string
    {
        $tags = $roulette->tags ?? [];

        if (!empty($tags['platform'])) {
            return self::PLATFORMS[$tags['platform'][0]] ?? 'By Genre';
        }

        if (!empty($tags['era'])) {
            return 'By Decade';
        }

        $hasJapan = (!empty($tags['country']) && in_array('JP', (array) $tags['country']))
                 || (!empty($tags['language']) && in_array('ja', (array) $tags['language']));

        if ($hasJapan && !empty($tags['genre']) && in_array('animation', (array) $tags['genre'])) {
            return $roulette->media_type === 'tv' ? 'Anime' : 'Anime';
        }

        if (!empty($tags['country']) || !empty($tags['language'])) {
            return $roulette->media_type === 'tv' ? 'World TV' : 'World Cinema';
        }

        return 'By Genre';
    }
}

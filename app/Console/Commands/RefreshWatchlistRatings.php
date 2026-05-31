<?php

namespace App\Console\Commands;

use App\Models\Watchlist;
use App\Services\TmdbClient;
use Illuminate\Console\Command;

class RefreshWatchlistRatings extends Command
{
    protected $signature = 'watchlist:refresh-ratings';
    protected $description = 'Refresh vote_average for all watchlist items from TMDB';

    public function handle(TmdbClient $tmdb): void
    {
        $items = Watchlist::all();

        if ($items->isEmpty()) {
            $this->info('Watchlist is empty.');
            return;
        }

        $this->info("Refreshing ratings for {$items->count()} items...");
        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        $updated = 0;
        foreach ($items as $item) {
            try {
                $info = $item->type === 'tv'
                    ? $tmdb->tvShow($item->tmdb_id)
                    : $tmdb->movie($item->tmdb_id);

                $rating = $info->vote_average ?? null;
                if ($rating !== null && (float) $rating !== (float) $item->vote_average) {
                    $item->update(['vote_average' => $rating]);
                    $updated++;
                }
            } catch (\Throwable) {
                // skip on API error
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Updated {$updated} of {$items->count()} items.");
    }
}

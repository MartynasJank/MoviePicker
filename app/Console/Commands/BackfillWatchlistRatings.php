<?php

namespace App\Console\Commands;

use App\Models\Watchlist;
use App\Services\TmdbClient;
use Illuminate\Console\Command;

class BackfillWatchlistRatings extends Command
{
    protected $signature = 'watchlist:backfill-ratings';
    protected $description = 'Fill in missing vote_average for watchlist items from TMDB';

    public function handle(TmdbClient $tmdb): void
    {
        $items = Watchlist::whereNull('vote_average')->get();

        if ($items->isEmpty()) {
            $this->info('All watchlist items already have ratings.');
            return;
        }

        $this->info("Backfilling ratings for {$items->count()} items...");
        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        $updated = 0;
        foreach ($items as $item) {
            try {
                $movie = $tmdb->movie($item->tmdb_id);
                $rating = $movie->vote_average ?? null;
                if ($rating) {
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
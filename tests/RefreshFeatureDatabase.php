<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

trait RefreshFeatureDatabase
{
    use RefreshDatabase;

    /**
     * Exercise the production migrations for these features in SQLite memory.
     * Unrelated historical roulette migrations contain MySQL-only data cleanup SQL.
     *
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing(): array
    {
        return ['--path' => [
            'database/migrations/2014_10_12_000000_create_users_table.php',
            'database/migrations/2026_05_19_115537_add_oauth_to_users_table.php',
            'database/migrations/2026_05_19_115537_create_watchlist_table.php',
            'database/migrations/2026_05_22_000001_add_vote_average_to_watchlist_table.php',
            'database/migrations/2026_05_22_100000_add_type_to_watchlist_table.php',
            'database/migrations/2026_05_29_110621_create_collab_batches_table.php',
            'database/migrations/2026_05_29_115033_update_collab_batches_add_voting_columns.php',
            'database/migrations/2026_06_02_112755_add_try_again_votes_to_collab_batches_table.php',
            'database/migrations/2026_06_02_134950_add_source_to_watchlist_table.php',
            'database/migrations/2026_10_31_102205_include_media_type_in_watchlist_unique_index.php',
        ]];
    }
}

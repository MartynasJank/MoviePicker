<?php

namespace App\Console\Commands;

use App\Models\PageView;
use App\Models\TmdbRequestLog;
use Illuminate\Console\Command;

class PruneAnalyticsData extends Command
{
    protected $signature   = 'analytics:prune';
    protected $description = 'Delete page_views and tmdb_request_logs older than 30 days';

    public function handle(): void
    {
        $cutoff = now()->subDays(30);

        $pageViews = PageView::where('created_at', '<', $cutoff)->delete();
        $this->info("Deleted {$pageViews} page_views older than 30 days.");

        $tmdbLogs = TmdbRequestLog::where('created_at', '<', $cutoff)->delete();
        $this->info("Deleted {$tmdbLogs} tmdb_request_logs older than 30 days.");
    }
}

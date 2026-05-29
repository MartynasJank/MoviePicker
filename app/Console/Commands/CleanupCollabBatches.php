<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupCollabBatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'collab:cleanup';
    protected $description = 'Delete expired collaborative batch sessions';

    public function handle()
    {
        $deleted = \App\Models\CollabBatch::where('expires_at', '<', now())->delete();
        $this->info("Deleted {$deleted} expired collab sessions.");
    }
}

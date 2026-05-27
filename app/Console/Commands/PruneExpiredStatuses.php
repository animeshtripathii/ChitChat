<?php

namespace App\Console\Commands;

use App\Models\Status;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PruneExpiredStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prune-expired-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically delete WhatsApp status updates that have exceeded their 24-hour expiration window.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredCount = Status::where('expires_at', '<', now())->count();

        if ($expiredCount > 0) {
            Status::where('expires_at', '<', now())->delete();
            $this->info("Successfully deleted {$expiredCount} expired status updates.");
            Log::info("STATUS PRUNE: Deleted {$expiredCount} expired status updates.");
        } else {
            $this->info("No expired status updates found.");
        }

        return 0;
    }
}

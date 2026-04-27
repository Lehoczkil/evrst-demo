<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

/**
 * Trim activity_logs older than the configured retention window.
 * Scheduled daily — see routes/console.php.
 */
class PruneActivityLog extends Command
{
    protected $signature = 'activity-log:prune {--days=90 : Retention window in days}';

    protected $description = 'Delete activity_log entries older than the retention window';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $deleted = ActivityLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned {$deleted} activity_log entries older than {$days} days (cutoff: {$cutoff->toDateTimeString()}).");

        return self::SUCCESS;
    }
}

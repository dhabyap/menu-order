<?php

namespace App\Console\Commands;

use App\Models\EventLog;
use App\Services\EventLogger;
use Illuminate\Console\Command;

class PurgeOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:purge {--days=90 : Number of days of event logs to retain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge event logs older than the retention threshold';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $this->error('Retention days must be at least 1.');
            return 1;
        }

        $threshold = now()->subDays($days);
        $deleted = EventLog::where('created_at', '<', $threshold)->delete();

        $this->info("Deleted {$deleted} event logs older than {$days} days.");

        EventLogger::success('log.purged', [
            'deleted_count' => $deleted,
            'days' => $days,
        ]);

        return 0;
    }
}

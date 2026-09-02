<?php

namespace App\Console\Commands;

use App\Models\TestAttempt;
use Illuminate\Console\Command;

class FailExpiredTestAttempts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:fail-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $updated = TestAttempt::query()
            ->where('status', TestAttempt::STATUS_IN_PROGRESS)
            ->whereHas('test', fn($q) => $q->where('duration', '>', 0))
            ->whereRaw('
            COALESCE(started_at, created_at) +
            (SELECT duration FROM tests WHERE tests.id = test_attempts.test_id) * INTERVAL \'1 minute\'
            < NOW()
            ')
            ->update(['status' => TestAttempt::STATUS_FAILED]);

        $this->info("Failed {$updated} expired attempts.");
    }
}

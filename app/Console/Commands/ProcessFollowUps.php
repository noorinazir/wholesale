<?php

namespace App\Console\Commands;

use App\Services\FollowUpService;
use Illuminate\Console\Command;

class ProcessFollowUps extends Command
{
    protected $signature = 'followups:process';
    protected $description = 'Process due follow-up emails and queue them for sending';

    public function handle(FollowUpService $service): int
    {
        $this->info('Processing due follow-ups...');

        $result = $service->processDueFollowUps();

        $this->info("Sent: {$result['sent']}, Skipped: {$result['skipped']}, Failed: {$result['failed']}");

        return self::SUCCESS;
    }
}

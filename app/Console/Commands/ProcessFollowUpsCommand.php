<?php

namespace App\Console\Commands;

use App\Services\FollowUpService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('followups:process')]
#[Description('Process due follow-up emails and queue them for sending')]
class ProcessFollowUpsCommand extends Command
{
    public function handle(): int
    {
        $this->info('Processing due follow-ups...');

        $service = app(FollowUpService::class);
        $result = $service->processDueFollowUps();

        $this->info("Follow-ups processed: {$result['sent']} sent, {$result['failed']} failed, {$result['skipped']} skipped.");

        return self::SUCCESS;
    }
}

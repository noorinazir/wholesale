<?php

namespace App\Console\Commands;

use App\Services\InboxService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('inbox:check')]
#[Description('Check inbox for new replies from vendors and update vendor statuses')]
class CheckInboxCommand extends Command
{
    public function handle(): int
    {
        $this->info('Checking inbox for new replies...');

        $service = app(InboxService::class);
        $result = $service->checkInbox();

        if ($result['success']) {
            $this->info($result['message']);
            return self::SUCCESS;
        }

        $this->error($result['message']);
        return self::FAILURE;
    }
}

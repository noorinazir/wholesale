<?php

namespace App\Console\Commands;

use App\Services\InboxService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckInbox extends Command
{
    protected $signature = 'inbox:check';
    protected $description = 'Check IMAP inbox for vendor replies and auto-update statuses';

    public function handle(InboxService $inboxService): int
    {
        $this->info('Checking inbox for replies...');

        $result = $inboxService->checkInbox();

        if ($result['success']) {
            $this->info($result['message']);
            Log::info('Inbox check completed: ' . $result['message']);
        } else {
            $this->warn($result['message']);
            Log::warning('Inbox check skipped: ' . $result['message']);
        }

        return Command::SUCCESS;
    }
}

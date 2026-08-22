<?php

namespace App\Console\Commands;

use App\Services\AmazonSpApiService;
use App\Services\AmazonFinancialImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchAmazonSettlementReport extends Command
{
    protected $signature = 'amazon:fetch-settlement
                            {--days=7 : Number of days to look back for reports}
                            {--auto-import : Automatically import parsed settlements}';

    protected $description = 'Fetch and optionally import Amazon settlement reports via SP-API';

    public function handle(AmazonSpApiService $api, AmazonFinancialImportService $importService): int
    {
        if (!$api->isConfigured()) {
            $this->warn('Amazon SP-API is not configured.');
            return self::FAILURE;
        }

        $days = (int)$this->option('days');
        $autoImport = $this->option('auto-import');
        $reportType = 'GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE';

        $this->info("Requesting settlement report (last {$days} days)...");

        try {
            $reportResponse = $api->createReport($reportType, now()->subDays($days), now());
            $reportId = $reportResponse['reportId'] ?? null;

            if (!$reportId) {
                $this->warn('Failed to create report request. Response: ' . json_encode($reportResponse));
                return self::FAILURE;
            }

            $this->info("Report requested. ID: {$reportId}. Waiting for completion...");

            $maxWait = 120;
            $waited = 0;
            $report = null;

            while ($waited < $maxWait) {
                sleep(10);
                $waited += 10;

                $status = $api->getReport($reportId);
                $processingStatus = $status['processingStatus'] ?? $status['status'] ?? 'Unknown';

                if ($processingStatus === 'DONE' || $processingStatus === 'DONE_NO_DATA') {
                    $report = $status;
                    break;
                }

                if (in_array($processingStatus, ['CANCELLED', 'FATAL'])) {
                    $this->error("Report processing failed: {$processingStatus}");
                    return self::FAILURE;
                }

                $this->line("  Waiting... ({$waited}s) Status: {$processingStatus}");
            }

            if (!$report) {
                $this->warn("Report not ready after {$maxWait}s. Will need to check later.");
                Log::info('Amazon settlement report not ready', ['report_id' => $reportId, 'waited' => $waited]);
                return self::SUCCESS;
            }

            $documentId = $report['reportDocumentId'] ?? null;
            if (!$documentId) {
                $this->error('No report document ID in completed report.');
                return self::FAILURE;
            }

            $this->info('Report ready. Downloading...');

            $document = $api->getReportDocument($documentId);
            $downloadUrl = $document['url'] ?? null;

            if (!$downloadUrl) {
                $this->error('No download URL in report document response.');
                return self::FAILURE;
            }

            $downloadResponse = \Illuminate\Support\Facades\Http::get($downloadUrl);
            $content = $downloadResponse->body();

            if (!$content) {
                $this->error('Failed to download report content.');
                return self::FAILURE;
            }

            $fileName = "settlement_{$reportId}_" . now()->format('Ymd_His') . '.tsv';
            $tempPath = storage_path("app/temp/{$fileName}");
            @mkdir(dirname($tempPath), 0755, true);
            file_put_contents($tempPath, $content);

            $this->info("Report saved to: {$tempPath}");

            if ($autoImport) {
                $this->info('Auto-importing settlement...');

                try {
                    $import = $importService->parseFile($tempPath, $fileName, null);

                    $this->info("Parsed: {$import->transactions->count()} transactions");
                    $this->info("  Matched orders: {$import->transactions->where('match_status', 'matched_order')->count()}");
                    $this->info("  Duplicates: {$import->transactions->where('match_status', 'duplicate')->count()}");
                    $this->info("  Unmatched: {$import->transactions->where('match_status', 'unmatched')->count()}");

                    $stats = $importService->commitImport($import);

                    $this->info("Committed: {$stats['expenses_created']} expenses, {$stats['orders_updated']} orders updated");

                    if (!empty($stats['errors'])) {
                        foreach (array_slice($stats['errors'], 0, 5) as $error) {
                            $this->warn("  Error: {$error}");
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("Import failed: " . $e->getMessage());
                    Log::error('Auto-import settlement failed', ['error' => $e->getMessage()]);
                    return self::FAILURE;
                }
            }

            @unlink($tempPath);

            $this->info('Done.');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('FetchAmazonSettlementReport failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }
}

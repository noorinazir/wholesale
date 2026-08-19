<?php

namespace App\Console\Commands;

use App\Services\AmazonSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAmazonData extends Command
{
    protected $signature = 'amazon:sync {type=full : Sync type: full, products, orders, inventory}';
    protected $description = 'Sync data from Amazon Seller Central SP-API';

    public function handle(AmazonSyncService $syncService): int
    {
        if (!$syncService->isConfigured()) {
            $this->error('Amazon SP-API is not configured. Add credentials in Settings > Amazon API.');
            return self::FAILURE;
        }

        $type = $this->argument('type');
        $this->info("Starting Amazon sync ({$type})...");

        try {
            switch ($type) {
                case 'products':
                    $products = \App\Models\Product::whereNotNull('asin')->where('asin', '!=', '')->get();
                    $synced = 0;
                    $errors = 0;
                    $bar = $this->output->createProgressBar($products->count());
                    $bar->start();
                    foreach ($products as $product) {
                        $result = $syncService->syncProduct($product);
                        $result['success'] ? $synced++ : $errors++;
                        $bar->advance();
                    }
                    $bar->finish();
                    $this->newLine();
                    $this->info("Products synced: {$synced}, errors: {$errors}");
                    break;

                case 'orders':
                    $result = $syncService->syncOrders(now()->subDays(7), now());
                    if ($result['success']) {
                        $this->info($result['message']);
                    } else {
                        $this->error($result['error']);
                    }
                    break;

                case 'inventory':
                    $result = $syncService->syncInventory();
                    if ($result['success']) {
                        $this->info($result['message']);
                    } else {
                        $this->error($result['error']);
                    }
                    break;

                default:
                    $results = $syncService->fullSync();
                    $this->info("Products: {$results['products']['synced']} synced, {$results['products']['errors']} errors");
                    $this->info("Orders: " . ($results['orders']['message'] ?? 'N/A'));
                    $this->info("Inventory: " . ($results['inventory']['message'] ?? 'N/A'));
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            Log::error('Amazon sync command failed', ['error' => $e->getMessage()]);
            $this->error('Sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

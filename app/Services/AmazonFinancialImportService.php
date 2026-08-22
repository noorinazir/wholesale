<?php

namespace App\Services;

use App\Models\AmazonOrder;
use App\Models\AmazonSettlementImport;
use App\Models\AmazonSettlementTransaction;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\AI\KimiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AmazonFinancialImportService
{
    public function __construct(
        private KimiService $kimi
    ) {}

    public function parseFile(string $filePath, string $fileName, ?int $userId): AmazonSettlementImport
    {
        $content = file_get_contents($filePath);
        $rows = $this->extractRows($content, $filePath);

        $import = AmazonSettlementImport::create([
            'file_name' => $fileName,
            'status' => 'pending',
            'raw_content' => mb_substr($content, 0, 50000),
            'user_id' => $userId,
        ]);

        try {
            $parsed = $this->parseWithKimi($rows);

            $settlementId = null;
            $startDate = null;
            $endDate = null;
            $totalAmount = 0;

            foreach ($parsed as $txn) {
                if (!$settlementId && !empty($txn['settlement_id'])) {
                    $settlementId = $txn['settlement_id'];
                }
                if (!$startDate || (!empty($txn['posted_date']) && $txn['posted_date'] < $startDate)) {
                    $startDate = $txn['posted_date'] ?? null;
                }
                if (!$endDate || (!empty($txn['posted_date']) && $txn['posted_date'] > $endDate)) {
                    $endDate = $txn['posted_date'] ?? null;
                }
                $totalAmount += (float)($txn['amount'] ?? 0);
            }

            $import->update([
                'settlement_id' => $settlementId,
                'settlement_start_date' => $startDate,
                'settlement_end_date' => $endDate,
                'total_amount' => round($totalAmount, 2),
                'status' => 'parsed',
                'parse_summary' => [
                    'total_rows' => count($rows),
                    'parsed_rows' => count($parsed),
                    'by_type' => $this->countByType($parsed),
                ],
            ]);

            $this->storeTransactions($import, $parsed);
            $this->matchTransactions($import);

            return $import->fresh(['transactions']);

        } catch (\Exception $e) {
            Log::error('Amazon financial import parse failed', [
                'import_id' => $import->id,
                'error' => $e->getMessage(),
            ]);

            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function extractRows(string $content, string $filePath): array
    {
        $rows = [];

        $isCsv = str_ends_with(strtolower($filePath), '.csv') || str_contains($content, ',');
        $isTsv = str_contains($content, "\t");

        if ($isTsv) {
            $lines = explode("\n", $content);
            $header = null;
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $cols = str_getcsv($line, "\t");
                if (!$header) {
                    $header = array_map(fn($h) => strtolower(trim($h)), $cols);
                    $header = array_map(fn($h) => str_replace(['-', ' '], '_', $h), $header);
                    continue;
                }
                if (count($cols) === count($header)) {
                    $rows[] = array_combine($header, $cols);
                }
            }
        } elseif ($isCsv) {
            $handle = tmpfile();
            fwrite($handle, $content);
            rewind($handle);
            $header = null;
            while (($row = fgetcsv($handle)) !== false) {
                if (!$header) {
                    $header = array_map(fn($h) => strtolower(trim($h)), $row);
                    $header = array_map(fn($h) => str_replace(['-', ' '], '_', $h), $header);
                    continue;
                }
                if (count($row) === count($header)) {
                    $rows[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        } else {
            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $rows[] = ['raw' => $line];
            }
        }

        return array_slice($rows, 0, 500);
    }

    private function parseWithKimi(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $firstRow = $rows[0];
        $hasStructuredHeaders = !isset($firstRow['raw']) && count($firstRow) > 2;

        if ($hasStructuredHeaders) {
            return $this->parseStructuredRows($rows);
        }

        $batchSize = 100;
        $allParsed = [];

        for ($i = 0; $i < count($rows); $i += $batchSize) {
            $batch = array_slice($rows, $i, $batchSize);
            $parsed = $this->parseUnstructuredBatch($batch);
            $allParsed = array_merge($allParsed, $parsed);
        }

        return $allParsed;
    }

    private function parseStructuredRows(array $rows): array
    {
        $parsed = [];

        foreach ($rows as $row) {
            // Strip any remaining quotes from values
            $row = array_map(fn($v) => is_string($v) ? trim($v, '"\'') : $v, $row);

            $txn = [
                'transaction_type' => $this->guessTransactionType($row),
                'order_id' => $this->pickField($row, ['amazon_order_id', 'order_id', 'amazon_order_id']),
                'merchant_order_id' => $this->pickField($row, ['merchant_order_id', 'merchant_order_id']),
                'sku' => $this->pickField($row, ['sku', 'seller_sku']),
                'asin' => $this->pickField($row, ['asin']),
                'product_name' => $this->pickField($row, ['product_name', 'title', 'description', 'sku_description', 'amount_description']),
                'amount' => (float)($this->pickField($row, ['amount', 'total', 'net_amount', 'transaction_amount', 'amount_transaction']) ?? 0),
                'fee_type' => $this->guessFeeType($row),
                'currency' => $this->pickField($row, ['currency', 'currency_code']) ?? 'USD',
                'transaction_description' => $this->pickField($row, ['transaction_description', 'description', 'transaction_type', 'type', 'amount_description', 'amount_type']),
                'posted_date' => $this->pickField($row, ['posted_date', 'date', 'transaction_date', 'posting_date', 'date_time']),
                'order_date' => $this->pickField($row, ['order_date', 'purchase_date']),
                'fulfillment_channel' => $this->pickField($row, ['fulfillment_channel', 'channel', 'fulfillment_id']),
                'settlement_id' => $this->pickField($row, ['settlement_id', 'settlement_id']),
            ];

            $parsed[] = $txn;
        }

        return $parsed;
    }

    private function parseUnstructuredBatch(array $batch): array
    {
        $linesText = implode("\n", array_map(fn($r) => $r['raw'] ?? json_encode($r), $batch));

        $prompt = <<<PROMPT
You are a financial data parser for Amazon settlement reports. Given raw data rows, extract and normalize each transaction into structured JSON.

For each row, output a JSON object with these fields:
- "transaction_type": one of: order, refund, fee, adjustment, transfer, service_fee, advertising, storage_fee, other
- "order_id": Amazon order ID if present, else null
- "merchant_order_id": merchant order ID if present, else null
- "sku": SKU if present, else null
- "asin": ASIN if present, else null
- "product_name": product name/description if present, else null
- "amount": numeric amount (positive for credits/income, negative for debits/fees)
- "fee_type": one of: fba, referral, storage, advertising, other, null (only for fee-type transactions)
- "currency": currency code, default "USD"
- "transaction_description": the original description text
- "posted_date": date in YYYY-MM-DD format if present, else null
- "order_date": order date in YYYY-MM-DD format if present, else null
- "fulfillment_channel": "FBA" or "FBM" if present, else null
- "settlement_id": settlement ID if present, else null

Return ONLY a JSON array of objects. No markdown, no explanation, just the array.

Raw data rows:
{$linesText}
PROMPT;

        $result = $this->kimi->chat([
            ['role' => 'user', 'content' => $prompt],
        ], [
            'max_tokens' => 4000,
            'temperature' => 0.1,
        ]);

        if (!$result['success'] || !$result['content']) {
            Log::warning('Kimi parse failed, falling back to raw storage', [
                'error' => $result['error'] ?? 'No content',
            ]);
            return array_map(fn($r) => [
                'transaction_type' => 'other',
                'order_id' => null,
                'merchant_order_id' => null,
                'sku' => null,
                'asin' => null,
                'product_name' => null,
                'amount' => 0,
                'fee_type' => null,
                'currency' => 'USD',
                'transaction_description' => $r['raw'] ?? json_encode($r),
                'posted_date' => null,
                'order_date' => null,
                'fulfillment_channel' => null,
                'settlement_id' => null,
            ], $batch);
        }

        $content = trim($result['content']);
        $content = preg_replace('/^```json\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);
        $content = trim($content);

        $parsed = json_decode($content, true);

        if (!is_array($parsed)) {
            Log::warning('Kimi returned invalid JSON', ['content' => mb_substr($content, 0, 500)]);
            return [];
        }

        return $parsed;
    }

    private function storeTransactions(AmazonSettlementImport $import, array $parsed): void
    {
        foreach ($parsed as $txn) {
            $cleanStr = fn($v) => is_string($v) ? trim($v, " \t\n\r\"'") : $v;

            AmazonSettlementTransaction::create([
                'import_id' => $import->id,
                'transaction_type' => $txn['transaction_type'] ?? 'other',
                'order_id' => $cleanStr($txn['order_id'] ?? null),
                'merchant_order_id' => $cleanStr($txn['merchant_order_id'] ?? null),
                'sku' => $cleanStr($txn['sku'] ?? null),
                'asin' => $cleanStr($txn['asin'] ?? null),
                'product_name' => $cleanStr($txn['product_name'] ?? null),
                'amount' => (float)($txn['amount'] ?? 0),
                'fee_type' => $cleanStr($txn['fee_type'] ?? null),
                'currency' => $txn['currency'] ?? 'USD',
                'transaction_description' => $cleanStr($txn['transaction_description'] ?? null),
                'posted_date' => $txn['posted_date'] ?? null,
                'order_date' => $txn['order_date'] ?? null,
                'fulfillment_channel' => $cleanStr($txn['fulfillment_channel'] ?? null),
                'raw_data' => $txn,
                'match_status' => 'unmatched',
            ]);
        }
    }

    public function matchTransactions(AmazonSettlementImport $import): void
    {
        $transactions = $import->transactions()->where('match_status', 'unmatched')->get();

        foreach ($transactions as $txn) {
            $match = $this->matchSingle($txn);
            $txn->update($match);
        }

        $stats = [
            'total' => $transactions->count(),
            'matched_order' => $transactions->filter(fn($t) => $t->fresh()->match_status === 'matched_order')->count(),
            'matched_product' => $transactions->filter(fn($t) => $t->fresh()->match_status === 'matched_product')->count(),
            'matched_vendor' => $transactions->filter(fn($t) => $t->fresh()->match_status === 'matched_vendor')->count(),
            'unmatched' => $transactions->filter(fn($t) => $t->fresh()->match_status === 'unmatched')->count(),
            'duplicate' => $transactions->filter(fn($t) => $t->fresh()->match_status === 'duplicate')->count(),
        ];
        Log::info('Settlement matching completed', ['import_id' => $import->id, 'stats' => $stats]);
    }

    private function matchSingle(AmazonSettlementTransaction $txn): array
    {
        $match = [
            'match_status' => 'unmatched',
            'amazon_order_id' => null,
            'product_id' => null,
            'vendor_id' => null,
            'match_notes' => null,
        ];

        $orderId = $txn->order_id ? trim($txn->order_id) : null;
        $asin = $txn->asin ? trim($txn->asin) : null;
        $sku = $txn->sku ? trim($txn->sku) : null;

        if ($orderId) {
            $order = AmazonOrder::where('amazon_order_id', $orderId)->first();
            if ($order) {
                $existing = AmazonSettlementTransaction::where('order_id', $orderId)
                    ->where('transaction_type', $txn->transaction_type)
                    ->where('fee_type', $txn->fee_type)
                    ->where('amount', $txn->amount)
                    ->where('id', '!=', $txn->id)
                    ->whereHas('import', fn($q) => $q->where('status', '!=', 'failed'))
                    ->exists();

                if ($existing) {
                    $match['match_status'] = 'duplicate';
                    $match['match_notes'] = 'Duplicate: same order_id, type, fee_type, and amount already imported';
                    return $match;
                }

                $match['amazon_order_id'] = $order->id;
                $match['product_id'] = $order->product_id;
                $match['vendor_id'] = $order->vendor_id;
                $match['match_status'] = 'matched_order';
                $match['match_notes'] = "Matched to AmazonOrder #{$order->id}";
                return $match;
            }
        }

        // Duplicate detection for fee-type transactions without order_id
        if (!$orderId && in_array($txn->transaction_type, ['fee', 'service_fee', 'storage_fee', 'advertising'])) {
            $query = AmazonSettlementTransaction::where('transaction_type', $txn->transaction_type)
                ->where('fee_type', $txn->fee_type)
                ->where('amount', $txn->amount)
                ->where('id', '!=', $txn->id)
                ->whereHas('import', fn($q) => $q->where('status', '!=', 'failed'));

            if ($txn->posted_date) {
                $query->where('posted_date', $txn->posted_date);
            }

            if ($query->exists()) {
                $match['match_status'] = 'duplicate';
                $match['match_notes'] = 'Duplicate: same fee type, amount, and date already imported';
                return $match;
            }
        }

        if ($asin) {
            $product = Product::where('asin', $asin)->first()
                ?? Product::where('asin', 'ilike', $asin)->first();
            if ($product) {
                $match['product_id'] = $product->id;
                $match['vendor_id'] = $product->vendor_id;
                $match['match_status'] = 'matched_product';
                $match['match_notes'] = "Matched to Product #{$product->id} by ASIN";
                return $match;
            }
        }

        if ($sku) {
            $product = Product::where('sku', $sku)->first()
                ?? Product::where('asin', $sku)->first();
            if ($product) {
                $match['product_id'] = $product->id;
                $match['vendor_id'] = $product->vendor_id;
                $match['match_status'] = 'matched_product';
                $match['match_notes'] = "Matched to Product #{$product->id} by SKU";
                return $match;
            }
        }

        if ($txn->product_name) {
            $productName = trim($txn->product_name);
            $product = Product::where('product_name', 'like', "%{$productName}%")->first();
            if ($product) {
                $match['product_id'] = $product->id;
                $match['vendor_id'] = $product->vendor_id;
                $match['match_status'] = 'matched_product';
                $match['match_notes'] = "Matched to Product #{$product->id} by name (fuzzy)";
                return $match;
            }
        }

        return $match;
    }

    public function commitImport(AmazonSettlementImport $import): array
    {
        $stats = [
            'expenses_created' => 0,
            'orders_updated' => 0,
            'duplicates_skipped' => 0,
            'unmatched_skipped' => 0,
            'errors' => [],
        ];

        $transactions = $import->transactions()
            ->whereNotIn('match_status', ['duplicate', 'ignored'])
            ->get();

        DB::transaction(function () use ($transactions, &$stats, $import) {
            foreach ($transactions as $txn) {
                try {
                    if (in_array($txn->transaction_type, ['fee', 'service_fee', 'storage_fee', 'advertising'])) {
                        $expense = $this->createExpenseFromTransaction($txn, $import);
                        if ($expense) {
                            $newStatus = $txn->match_status;
                            if ($txn->match_status === 'unmatched') {
                                $newStatus = $txn->vendor_id
                                    ? 'matched_vendor'
                                    : ($txn->product_id ? 'matched_product' : 'unmatched');
                            }
                            $txn->update([
                                'expense_id' => $expense->id,
                                'match_status' => $newStatus,
                            ]);
                            $stats['expenses_created']++;
                        }
                    } elseif (in_array($txn->transaction_type, ['order', 'refund'])) {
                        $updated = $this->reconcileOrderFromTransaction($txn);
                        if ($updated) {
                            $stats['orders_updated']++;
                        }
                    }
                } catch (\Exception $e) {
                    $stats['errors'][] = "Transaction #{$txn->id}: " . $e->getMessage();
                }
            }

            $import->update(['status' => 'imported']);
        });

        $stats['duplicates_skipped'] = $import->transactions()->where('match_status', 'duplicate')->count();
        $stats['unmatched_skipped'] = $import->transactions()->where('match_status', 'unmatched')->count();

        return $stats;
    }

    private function createExpenseFromTransaction(AmazonSettlementTransaction $txn, AmazonSettlementImport $import): ?Expense
    {
        $amount = abs((float)$txn->amount);
        if ($amount <= 0) {
            return null;
        }

        $categoryMap = [
            'fba' => 'amazon_fees',
            'referral' => 'amazon_fees',
            'storage' => 'storage',
            'advertising' => 'advertising',
            'other' => 'other',
        ];

        $category = $categoryMap[$txn->fee_type ?? 'other'] ?? 'other';

        $existing = Expense::where('metadata->amazon_settlement_txn_id', $txn->id)->exists();
        if ($existing) {
            return null;
        }

        $description = ucfirst(str_replace('_', ' ', $txn->fee_type ?? 'fee'));
        if ($txn->transaction_description) {
            $description .= ': ' . $txn->transaction_description;
        }

        return Expense::create([
            'expense_number' => Expense::generateExpenseNumber(),
            'vendor_id' => $txn->vendor_id,
            'product_id' => $txn->product_id,
            'category' => $category,
            'description' => mb_substr($description, 0, 255),
            'amount' => $amount,
            'currency' => $txn->currency ?? 'USD',
            'expense_date' => $txn->posted_date ?? now(),
            'status' => 'approved',
            'notes' => 'Auto-imported from Amazon settlement (Import #' . $import->id . ')',
            'metadata' => [
                'amazon_settlement_txn_id' => $txn->id,
                'amazon_settlement_import_id' => $import->id,
                'fee_type' => $txn->fee_type,
                'order_id' => $txn->order_id,
                'asin' => $txn->asin,
            ],
        ]);
    }

    private function reconcileOrderFromTransaction(AmazonSettlementTransaction $txn): bool
    {
        if (!$txn->amazon_order_id) {
            return false;
        }

        $order = AmazonOrder::find($txn->amazon_order_id);
        if (!$order) {
            return false;
        }

        $amount = (float)$txn->amount;
        $updated = false;

        if ($txn->transaction_type === 'refund') {
            if (!in_array($order->order_status, ['returned', 'refunded'])) {
                $order->update(['order_status' => 'refunded']);
                $order->recalculate();
                $updated = true;
            }
        } elseif ($txn->transaction_type === 'order' && $amount > 0) {
            $recordedRevenue = (float)$order->total_revenue;
            if (abs($recordedRevenue - $amount) > 0.01) {
                $order->update(['total_revenue' => round($amount, 2)]);
                $updated = true;
            }

            // If referral fee was never calculated but rate is set, calculate it now
            if ((float)$order->amazon_referral_fee <= 0 && (float)$order->breakaway_referral_rate > 0) {
                $updated = true;
            }

            if ($updated) {
                $order->recalculate();
            }
        }

        return $updated;
    }

    private function pickField(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && !empty(trim($row[$key]))) {
                return trim($row[$key]);
            }
        }
        return null;
    }

    private function guessTransactionType(array $row): string
    {
        // First, check if there's an explicit transaction_type or amount_type column
        $explicitType = $this->pickField($row, ['transaction_type', 'amount_type', 'type']);
        if ($explicitType) {
            $typeLower = strtolower(trim($explicitType));
            $typeMap = [
                'order' => 'order',
                'refund' => 'refund',
                'service fee' => 'service_fee',
                'service_fee' => 'service_fee',
                'fba inventory fee' => 'fee',
                'cost of advertising' => 'advertising',
                'advertising' => 'advertising',
                'sponsored' => 'advertising',
                'storage fee' => 'storage_fee',
                'commission' => 'fee',
                'referral' => 'fee',
                'adjustment' => 'adjustment',
                'transfer' => 'transfer',
                'payout' => 'transfer',
            ];
            foreach ($typeMap as $needle => $result) {
                if (str_contains($typeLower, $needle)) {
                    return $result;
                }
            }
        }

        // Fallback: scan all values for keywords
        $desc = strtolower(implode(' ', array_values($row)));

        if (str_contains($desc, 'refund')) return 'refund';
        if (str_contains($desc, 'order')) return 'order';
        if (str_contains($desc, 'advertising') || str_contains($desc, 'sponsored') || str_contains($desc, 'ppc')) return 'advertising';
        if (str_contains($desc, 'storage')) return 'storage_fee';
        if (str_contains($desc, 'fba') || str_contains($desc, 'fulfillment')) return 'fee';
        if (str_contains($desc, 'commission') || str_contains($desc, 'referral')) return 'fee';
        if (str_contains($desc, 'transfer') || str_contains($desc, 'payout')) return 'transfer';
        if (str_contains($desc, 'adjustment')) return 'adjustment';
        if (str_contains($desc, 'service')) return 'service_fee';

        return 'other';
    }

    private function guessFeeType(array $row): ?string
    {
        $typeDesc = $this->pickField($row, ['transaction_type', 'amount_type', 'amount_description', 'description', 'type']);
        if ($typeDesc) {
            $descLower = strtolower($typeDesc);
            if (str_contains($descLower, 'fba') || str_contains($descLower, 'fulfillment')) return 'fba';
            if (str_contains($descLower, 'commission') || str_contains($descLower, 'referral')) return 'referral';
            if (str_contains($descLower, 'storage')) return 'storage';
            if (str_contains($descLower, 'advertising') || str_contains($descLower, 'sponsored') || str_contains($descLower, 'ppc')) return 'advertising';
        }

        $desc = strtolower(implode(' ', array_values($row)));

        if (str_contains($desc, 'fba') || str_contains($desc, 'fulfillment')) return 'fba';
        if (str_contains($desc, 'commission') || str_contains($desc, 'referral')) return 'referral';
        if (str_contains($desc, 'storage')) return 'storage';
        if (str_contains($desc, 'advertising') || str_contains($desc, 'sponsored') || str_contains($desc, 'ppc')) return 'advertising';

        return null;
    }

    private function countByType(array $parsed): array
    {
        $counts = [];
        foreach ($parsed as $txn) {
            $type = $txn['transaction_type'] ?? 'other';
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }
        return $counts;
    }
}

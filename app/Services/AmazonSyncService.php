<?php

namespace App\Services;

use App\Models\Product;
use App\Models\AmazonOrder;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AmazonSyncService
{
    public function __construct(
        private AmazonSpApiService $api
    ) {}

    public function isConfigured(): bool
    {
        return $this->api->isConfigured();
    }

    // === Product Sync ===

    public function syncProduct(Product $product): array
    {
        if (!$product->asin) {
            return ['success' => false, 'error' => 'Product has no ASIN'];
        }

        try {
            $product->update(['amazon_sync_status' => 'syncing']);

            $catalogData = $this->api->getCatalogItem($product->asin);
            $item = $catalogData['items'][0] ?? $catalogData;

            $updates = [];
            if (!empty($item['attributes']['item_name'][0]['value'])) {
                $updates['product_name'] = $item['attributes']['item_name'][0]['value'];
            }
            if (!empty($item['attributes']['product_type'][0]['value'])) {
                $updates['product_category'] = $item['attributes']['product_type'][0]['value'];
            }
            if (!empty($item['images'][0]['images'][0]['url'])) {
                $updates['image_url'] = $item['images'][0]['images'][0]['url'];
            }
            if (!empty($item['attributes']['seller_sku'][0]['value'])) {
                $updates['sku'] = $item['attributes']['seller_sku'][0]['value'];
            }

            $updates['amazon_last_synced_at'] = now();
            $updates['amazon_sync_status'] = 'synced';
            $updates['amazon_raw_data'] = $item;

            $product->update($updates);

            // Sync pricing
            $this->syncProductPricing($product);

            // Sync fees
            $this->syncProductFees($product);

            $product->recalculate();

            return ['success' => true, 'message' => 'Product synced from Amazon'];

        } catch (\Exception $e) {
            $product->update(['amazon_sync_status' => 'error']);
            Log::error('Amazon product sync failed', ['product_id' => $product->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncProductPricing(Product $product): array
    {
        if (!$product->asin) {
            return ['success' => false, 'error' => 'No ASIN'];
        }

        try {
            $priceData = $this->api->getItemPrice($product->asin);
            $offers = $priceData['payload']['Offers'] ?? [];

            $buyBoxPrice = null;
            $fulfillmentChannel = 'FBM';

            foreach ($offers as $offer) {
                if (($offer['IsBuyBoxWinner'] ?? false) === true) {
                    $buyBoxPrice = (float)($offer['ListingPrice']['Amount'] ?? 0);
                    $fulfillmentChannel = $offer['FulfillmentChannel'] ?? 'FBM';
                    break;
                }
            }

            if ($buyBoxPrice !== null && $buyBoxPrice > 0) {
                if ($fulfillmentChannel === 'Amazon') {
                    $product->update(['fba_buy_box_price' => $buyBoxPrice]);
                } else {
                    $product->update(['fbm_buy_box_price' => $buyBoxPrice]);
                }

                // Always update sell price from buy box to keep pricing current
                $product->update(['amazon_sell_price' => $buyBoxPrice]);
            }

            $product->recalculate();

            return ['success' => true, 'buy_box_price' => $buyBoxPrice];

        } catch (\Exception $e) {
            Log::error('Amazon pricing sync failed', ['asin' => $product->asin, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncProductFees(Product $product): array
    {
        if (!$product->asin) {
            return ['success' => false, 'error' => 'No ASIN'];
        }

        $price = (float)$product->amazon_sell_price;
        if ($price <= 0) {
            $price = (float)($product->fba_buy_box_price ?: 10.00);
        }

        try {
            $feeData = $this->api->getFeesEstimate($product->asin, $price);
            $estimate = $feeData['payload']['FeesEstimateResult']['FeesEstimate'] ?? null;

            if ($estimate) {
                $fbaFee = 0;
                $referralFee = 0;
                $referralRate = 0;

                foreach ($estimate['FeeDetailList'] ?? [] as $fee) {
                    $feeType = $fee['FeeType'] ?? '';
                    $amount = (float)($fee['FeeAmount']['Amount'] ?? 0);

                    if (str_contains(strtolower($feeType), 'fba')) {
                        $fbaFee = $amount;
                    } elseif (str_contains(strtolower($feeType), 'referral')) {
                        $referralFee = $amount;
                        if ($price > 0) {
                            $referralRate = round(($amount / $price) * 100, 2);
                        }
                    }
                }

                // fba_fee now stores total Amazon fee (FBA fulfillment + referral combined)
                $totalAmazonFee = $fbaFee + $referralFee;

                $product->update([
                    'fba_fee' => round($totalAmazonFee, 2),
                    'referral_fee_percent' => $referralRate,
                ]);

                $product->recalculate();

                return ['success' => true, 'fba_fee' => $totalAmazonFee, 'referral_fee' => $referralFee];
            }

            return ['success' => false, 'error' => 'No fee estimate in response'];

        } catch (\Exception $e) {
            Log::error('Amazon fees sync failed', ['asin' => $product->asin, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // === Orders Sync ===

    public function syncOrders(Carbon $from, Carbon $to): array
    {
        try {
            $imported = 0;
            $skipped = 0;
            $errors = [];
            $nextToken = null;

            // Paginate through all orders in the date range
            do {
                $ordersData = $this->api->getOrders($from, $to, 100, $nextToken);
                $orders = $ordersData['payload']['Orders'] ?? [];
                $nextToken = $ordersData['payload']['NextToken'] ?? null;

                foreach ($orders as $amazonOrder) {
                    $orderId = $amazonOrder['AmazonOrderId'] ?? '';

                    if (AmazonOrder::where('amazon_order_id', $orderId)->exists()) {
                        $skipped++;
                        continue;
                    }

                    try {
                        $orderItems = $this->api->getOrderItems($orderId);
                        $items = $orderItems['payload']['OrderItems'] ?? [];

                        foreach ($items as $item) {
                            $asin = $item['ASIN'] ?? '';
                            $product = Product::where('asin', $asin)->first();

                            $quantity = (int)($item['QuantityOrdered'] ?? 1);
                            $itemPrice = (float)($item['ItemPrice']['Amount'] ?? 0);
                            $totalRevenue = $itemPrice * $quantity;

                            $referralRate = $product ? (float)$product->referral_fee_percent : 0;

                            // Calculate tax based on shipping state
                            $taxState = strtoupper(substr($amazonOrder['ShippingAddress']['StateOrRegion'] ?? '', 0, 2));
                            $taxData = ['rate' => 0, 'amount' => 0];
                            if (!empty($taxState) && strlen($taxState) === 2) {
                                $taxData = AmazonOrder::calculateTax($totalRevenue, $taxState);
                            }

                            // Auto-link to PO that contains this product, preferring confirmed/received POs
                            $poId = null;
                            if ($product?->id) {
                                $poItem = \App\Models\PurchaseOrderItem::where('product_id', $product->id)
                                    ->whereHas('purchaseOrder', function ($q) {
                                        $q->whereIn('status', ['confirmed', 'partial_received', 'in_production', 'shipped', 'received']);
                                    })
                                    ->latest()
                                    ->first();
                                $poId = $poItem?->purchase_order_id;
                            }

                            $order = AmazonOrder::create([
                                'amazon_order_id' => $orderId,
                                'product_id' => $product?->id,
                                'vendor_id' => $product?->vendor_id,
                                'purchase_order_id' => $poId,
                                'product_name' => $item['Title'] ?? ($product?->product_name ?? 'Unknown'),
                                'asin' => $asin,
                                'sku' => $item['SellerSKU'] ?? null,
                                'fulfillment_channel' => ($amazonOrder['FulfillmentChannel'] ?? 'MFN') === 'AFN' ? 'FBA' : 'FBM',
                                'amazon_marketplace' => $amazonOrder['MarketplaceId'] ?? 'US',
                                'order_date' => Carbon::parse($amazonOrder['PurchaseDate'])->toDateString(),
                                'ship_date' => isset($amazonOrder['EarliestShipDate']) ? Carbon::parse($amazonOrder['EarliestShipDate'])->toDateString() : null,
                                'delivery_date' => isset($amazonOrder['LatestDeliveryDate']) ? Carbon::parse($amazonOrder['LatestDeliveryDate'])->toDateString() : null,
                                'order_status' => $this->mapOrderStatus($amazonOrder['OrderStatus'] ?? 'Pending'),
                                'quantity' => $quantity,
                                'sale_price' => round($itemPrice, 2),
                                'total_revenue' => round($totalRevenue, 2),
                                'product_cost' => $product ? round((float)$product->buying_price * $quantity, 2) : 0,
                                'fba_fee' => $product ? round((float)$product->fba_fee * $quantity, 2) : 0,
                                'amazon_referral_fee' => 0,
                                'breakaway_referral_rate' => $referralRate,
                                'shipping_cost' => $product ? round((float)$product->shipping_cost * $quantity, 2) : 0,
                                'labeling_cost' => $product ? round((float)$product->labeling_cost * $quantity, 2) : 0,
                                'other_costs' => $product ? round((float)$product->other_costs * $quantity, 2) : 0,
                                'operation_cost' => $product ? round((float)($product->operation_cost ?? 0) * $quantity, 2) : 0,
                                'tax_collected' => $taxData['amount'],
                                'tax_rate' => $taxData['rate'],
                                'tax_state' => $taxState ?: null,
                                'customer_name' => trim($amazonOrder['BuyerInfo']['BuyerName'] ?? ''),
                                'customer_state' => $amazonOrder['ShippingAddress']['StateOrRegion'] ?? null,
                                'customer_city' => $amazonOrder['ShippingAddress']['City'] ?? null,
                                'customer_zip' => $amazonOrder['ShippingAddress']['PostalCode'] ?? null,
                                'amazon_last_synced_at' => now(),
                                'amazon_sync_status' => 'synced',
                            ]);

                            $order->recalculate();

                            // Stock handling: deduct for active orders, restock for returns
                            if ($product) {
                                $mappedStatus = $this->mapOrderStatus($amazonOrder['OrderStatus'] ?? 'Pending');
                                if (in_array($mappedStatus, ['returned', 'refunded'])) {
                                    // Return/refund: restock and set return cost
                                    $product->adjustStock($quantity, 'add');
                                    if ((float)$order->return_cost <= 0) {
                                        $returnShipping = (float)$order->shipping_cost;
                                        $restockingFee = (float)$order->fba_fee * 0.5;
                                        $order->update(['return_cost' => round($returnShipping + $restockingFee, 2)]);
                                        $order->recalculate();
                                    }
                                } elseif ($mappedStatus !== 'cancelled') {
                                    // Active order: deduct stock
                                    $product->adjustStock($quantity, 'subtract');
                                }
                            }

                            $imported++;
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Order {$orderId}: " . $e->getMessage();
                    }
                }
            } while ($nextToken);

            $message = "Imported {$imported} orders from Amazon";
            if ($skipped > 0) $message .= ", skipped {$skipped} duplicates";
            if (count($errors) > 0) $message .= ". Errors: " . implode('; ', array_slice($errors, 0, 3));

            return ['success' => true, 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'message' => $message];

        } catch (\Exception $e) {
            Log::error('Amazon orders sync failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function mapOrderStatus(string $amazonStatus): string
    {
        return match(strtolower($amazonStatus)) {
            'pending' => 'pending',
            'unshipped' => 'processing',
            'partiallyshipped' => 'processing',
            'shipped' => 'shipped',
            'canceled' => 'cancelled',
            'unfulfillable' => 'cancelled',
            default => 'pending',
        };
    }

    // === Inventory Sync ===

    public function syncInventory(): array
    {
        try {
            $invData = $this->api->getFbaInventory();
            $summaries = $invData['payload']['inventorySummaries'] ?? [];

            $updated = 0;
            foreach ($summaries as $summary) {
                $asin = $summary['asin'] ?? '';
                $product = Product::where('asin', $asin)->first();

                if ($product) {
                    $fulfillableQty = (int)($summary['inventoryDetails']['fulfillableQuantity'] ?? 0);
                    $inboundQty = (int)($summary['inventoryDetails']['inboundWorkingQuantity'] ?? 0);
                    $unreserved = (int)($summary['inventoryDetails']['unreservedQuantity'] ?? $fulfillableQty);
                    $total = $unreserved + $inboundQty;

                    $product->update(['stock_quantity' => $total]);
                    $updated++;
                }
            }

            return ['success' => true, 'updated' => $updated, 'message' => "Updated inventory for {$updated} products"];

        } catch (\Exception $e) {
            Log::error('Amazon inventory sync failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // === Full Sync ===

    public function fullSync(): array
    {
        $results = [];

        // Sync all products with ASINs
        $products = Product::whereNotNull('asin')->where('asin', '!=', '')->get();
        $productResults = ['synced' => 0, 'errors' => 0];
        foreach ($products as $product) {
            $result = $this->syncProduct($product);
            if ($result['success']) {
                $productResults['synced']++;
            } else {
                $productResults['errors']++;
            }
        }
        $results['products'] = $productResults;

        // Sync orders from last 7 days
        $orderResult = $this->syncOrders(now()->subDays(7), now());
        $results['orders'] = $orderResult;

        // Sync inventory
        $invResult = $this->syncInventory();
        $results['inventory'] = $invResult;

        return $results;
    }
}

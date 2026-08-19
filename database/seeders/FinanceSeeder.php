<?php

namespace Database\Seeders;

use App\Models\Vendor;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\AmazonOrder;
use App\Models\Expense;
use App\Models\TaxRate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        // === Tax Rates (US states) ===
        $taxData = TaxRate::seedUsStates();
        foreach ($taxData as $row) {
            TaxRate::updateOrCreate(
                ['state_code' => $row[0]],
                [
                    'state_name' => $row[1],
                    'sales_tax_rate' => $row[2],
                    'additional_rate' => $row[3],
                    'combined_rate' => $row[4],
                    'has_marketplace_facilitator' => $row[5],
                ]
            );
        }

        // === Vendor ===
        $vendor = Vendor::firstOrCreate(
            ['brand_name' => 'Test Wholesale Co'],
            [
                'contact_email' => 'sales@testwholesale.com',
                'phone' => '555-0100',
                'website' => 'https://testwholesale.example.com',
                'state' => 'CA',
                'city' => 'Los Angeles',
                'status' => 'approved',
            ]
        );

        // === Products ===
        $product1 = Product::firstOrCreate(
            ['asin' => 'BTEST001'],
            [
                'vendor_id' => $vendor->id,
                'product_name' => 'Wireless Bluetooth Earbuds Pro',
                'upc' => '123456789001',
                'buying_price' => 12.50,
                'amazon_sell_price' => 29.99,
                'fba_fee' => 4.50,
                'shipping_cost' => 1.20,
                'labeling_cost' => 0.30,
                'other_costs' => 0.50,
                'operation_cost' => 0.75,
                'referral_fee_percent' => 15.00,
                'stock_quantity' => 0,
            ]
        );

        $product2 = Product::firstOrCreate(
            ['asin' => 'BTEST002'],
            [
                'vendor_id' => $vendor->id,
                'product_name' => 'USB-C Fast Charger 65W',
                'upc' => '123456789002',
                'buying_price' => 8.00,
                'amazon_sell_price' => 19.99,
                'fba_fee' => 3.20,
                'shipping_cost' => 0.80,
                'labeling_cost' => 0.20,
                'other_costs' => 0.30,
                'operation_cost' => 0.50,
                'referral_fee_percent' => 15.00,
                'stock_quantity' => 0,
            ]
        );

        $product1->recalculate();
        $product2->recalculate();

        // === Purchase Order (confirmed, paid) ===
        $po = PurchaseOrder::firstOrCreate(
            ['po_number' => 'PO-2026-00001'],
            [
                'vendor_id' => $vendor->id,
                'order_date' => now()->subDays(10),
                'expected_delivery_date' => now()->addDays(5),
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'payment_method' => 'Wire Transfer',
                'payment_terms' => 'Net 30',
                'shipping_cost' => 25.00,
                'tax_amount' => 8.50,
                'discount_amount' => 0,
                'amount_paid' => 0, // will be set after recalculate
                'notes' => 'Test PO for verification',
            ]
        );

        $item1 = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product1->id,
            'product_name' => $product1->product_name,
            'asin' => $product1->asin,
            'upc' => $product1->upc,
            'quantity_ordered' => 100,
            'quantity_received' => 0,
            'unit_cost' => 12.50,
            'line_total' => 1250.00,
            'unit_shipping' => 1.20,
            'unit_labeling' => 0.30,
            'unit_other_costs' => 0.50,
        ]);

        $item2 = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product2->id,
            'product_name' => $product2->product_name,
            'asin' => $product2->asin,
            'upc' => $product2->upc,
            'quantity_ordered' => 50,
            'quantity_received' => 0,
            'unit_cost' => 8.00,
            'line_total' => 400.00,
            'unit_shipping' => 0.80,
            'unit_labeling' => 0.20,
            'unit_other_costs' => 0.30,
        ]);

        $po->recalculate();
        $po->update(['amount_paid' => $po->total_amount]);

        // Auto-generate expense for the paid PO
        Expense::create([
            'expense_number' => Expense::generateExpenseNumber(),
            'vendor_id' => $vendor->id,
            'purchase_order_id' => $po->id,
            'category' => 'inventory',
            'description' => "Purchase Order {$po->po_number} payment (paid)",
            'amount' => $po->total_amount,
            'expense_date' => $po->order_date,
            'status' => 'approved',
            'payment_method' => $po->payment_method,
            'notes' => 'Auto-generated from PO payment update',
        ]);

        // === A sample Amazon sale ===
        $sale = AmazonOrder::create([
            'amazon_order_id' => '112-TEST-0001',
            'product_id' => $product1->id,
            'vendor_id' => $vendor->id,
            'purchase_order_id' => $po->id,
            'product_name' => $product1->product_name,
            'asin' => $product1->asin,
            'fulfillment_channel' => 'FBA',
            'amazon_marketplace' => 'US',
            'order_date' => now()->subDays(3),
            'order_status' => 'delivered',
            'quantity' => 5,
            'sale_price' => 29.99,
            'total_revenue' => 149.95,
            'product_cost' => 12.50 * 5,
            'fba_fee' => 4.50 * 5,
            'amazon_referral_fee' => 0,
            'breakaway_referral_rate' => 15.00,
            'shipping_cost' => 1.20 * 5,
            'labeling_cost' => 0.30 * 5,
            'other_costs' => 0.50 * 5,
            'operation_cost' => 0.75 * 5,
            'tax_state' => 'CA',
            'tax_rate' => 9.50,
            'tax_collected' => 14.25,
        ]);
        $sale->recalculate();

        // === Another PO (shipped, unpaid) ===
        $po2 = PurchaseOrder::firstOrCreate(
            ['po_number' => 'PO-2026-00002'],
            [
                'vendor_id' => $vendor->id,
                'order_date' => now()->subDays(5),
                'expected_delivery_date' => now()->addDays(10),
                'status' => 'shipped',
                'payment_status' => 'unpaid',
                'payment_method' => 'Credit Card',
                'payment_terms' => 'Net 15',
                'shipping_cost' => 15.00,
                'tax_amount' => 5.00,
                'discount_amount' => 0,
                'amount_paid' => 0,
                'notes' => 'Second test PO - awaiting delivery',
            ]
        );

        PurchaseOrderItem::create([
            'purchase_order_id' => $po2->id,
            'product_id' => $product1->id,
            'product_name' => $product1->product_name,
            'asin' => $product1->asin,
            'upc' => $product1->upc,
            'quantity_ordered' => 200,
            'quantity_received' => 0,
            'unit_cost' => 12.00,
            'line_total' => 2400.00,
            'unit_shipping' => 1.00,
            'unit_labeling' => 0.25,
            'unit_other_costs' => 0.40,
        ]);

        $po2->recalculate();

        echo "\n========================================\n";
        echo "  FINANCE SEEDER COMPLETE\n";
        echo "========================================\n";
        echo "  Login: admin@wholesale.com\n";
        echo "  Password: password\n";
        echo "========================================\n";
        echo "  Vendor: Test Wholesale Co\n";
        echo "  Products: 2 (Earbuds Pro, USB-C Charger)\n";
        echo "  PO #1: PO-2026-00001 (confirmed, paid, $" . number_format($po->total_amount, 2) . ")\n";
        echo "  PO #2: PO-2026-00002 (shipped, unpaid)\n";
        echo "  Sale: 112-TEST-0001 (5 units Earbuds, delivered)\n";
        echo "  Expense: Auto-created for PO #1\n";
        echo "  Tax Rates: All US states seeded\n";
        echo "========================================\n\n";
    }
}

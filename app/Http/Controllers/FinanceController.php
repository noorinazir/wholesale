<?php

namespace App\Http\Controllers;

use App\Models\AmazonOrder;
use App\Models\AmazonSettlementImport;
use App\Models\AmazonSettlementTransaction;
use App\Models\Expense;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\TaxRate;
use App\Models\Vendor;
use App\Models\Product;
use App\Services\ProfitLossService;
use App\Services\AuditLogService;
use App\Services\AmazonSpApiService;
use App\Services\AmazonSyncService;
use App\Services\AmazonFinancialImportService;
use App\Services\AI\KimiService;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function __construct(
        private AuditLogService $auditLog,
        private ProfitLossService $plService,
        private AmazonFinancialImportService $importService
    ) {}

    // === Finance Dashboard ===
    public function dashboard()
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // Current month KPIs
        $monthSummary = $this->plService->getOverallSummary($monthStart, $monthEnd);
        $lastMonthSummary = $this->plService->getOverallSummary($lastMonthStart, $lastMonthEnd);

        // Calculate trends
        $revenueTrend = $lastMonthSummary['total_revenue'] > 0
            ? (($monthSummary['total_revenue'] - $lastMonthSummary['total_revenue']) / $lastMonthSummary['total_revenue']) * 100
            : 0;
        $profitTrend = $lastMonthSummary['net_profit'] > 0
            ? (($monthSummary['net_profit'] - $lastMonthSummary['net_profit']) / $lastMonthSummary['net_profit']) * 100
            : 0;

        // All-time totals (exclude cancelled and returned/refunded from revenue)
        $totalRevenue = AmazonOrder::whereNotIn('order_status', ['cancelled', 'returned', 'refunded'])->sum('total_revenue');
        $totalProfit = AmazonOrder::whereNotIn('order_status', ['cancelled', 'returned', 'refunded'])->sum('net_profit');
        $totalOrders = AmazonOrder::whereNotIn('order_status', ['cancelled'])->count();
        $totalUnits = AmazonOrder::whereNotIn('order_status', ['cancelled', 'returned', 'refunded'])->sum('quantity');
        $totalReturns = AmazonOrder::whereIn('order_status', ['returned', 'refunded'])->count();
        $totalReturnCost = AmazonOrder::whereIn('order_status', ['returned', 'refunded'])->sum('total_cost');

        // Recent sales
        $recentSales = AmazonOrder::with(['product:id,product_name,asin', 'vendor:id,brand_name'])
            ->latest()->limit(6)->get();

        // Recent POs
        $recentPOs = PurchaseOrder::with(['vendor:id,brand_name', 'items'])
            ->latest()->limit(5)->get();

        // Recent expenses
        $recentExpenses = Expense::with(['vendor:id,brand_name', 'product:id,product_name'])
            ->latest('expense_date')->limit(5)->get();

        // Top products this month
        $topProducts = $this->plService->getPerProductBreakdown($monthStart, $monthEnd);
        $topProducts = array_slice($topProducts, 0, 5);

        // Monthly trend (6 months)
        $monthlyTrend = $this->plService->getMonthlyTrend(6);

        // Low stock alerts
        $lowStock = Product::where('stock_quantity', '<', 10)
            ->whereNotNull('asin')->orderBy('stock_quantity')
            ->limit(5)->get(['id', 'product_name', 'asin', 'stock_quantity']);

        // PO stats
        $pendingPOs = PurchaseOrder::whereIn('status', ['draft', 'submitted', 'confirmed'])->count();
        $pendingPOValue = PurchaseOrder::whereIn('status', ['draft', 'submitted', 'confirmed'])->sum('total_amount');
        $poStatusCounts = PurchaseOrder::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')->pluck('count', 'status')->toArray();
        $poTotalValue = PurchaseOrder::sum('total_amount');
        $poReceivedValue = PurchaseOrder::where('status', 'received')->sum('total_amount');

        // Items pending receipt
        $pendingReceiptUnits = PurchaseOrderItem::whereHas('purchaseOrder', function ($q) {
            $q->whereNotIn('status', ['cancelled', 'received']);
        })->whereColumn('quantity_received', '<', 'quantity_ordered')
          ->selectRaw('SUM(quantity_ordered - quantity_received) as pending')
          ->value('pending') ?? 0;

        // Inventory value
        $inventoryValue = Product::where('stock_quantity', '>', 0)
            ->selectRaw('SUM(stock_quantity * total_cost) as value')->value('value') ?? 0;
        $inventoryUnits = Product::sum('stock_quantity');
        $productCount = Product::count();

        // Expense stats this month
        $monthExpenses = Expense::whereBetween('expense_date', [$monthStart, $monthEnd])
            ->where('status', '!=', 'rejected')
            ->where('notes', 'not like', 'Auto-generated from Amazon sale%')
            ->sum('amount');
        $expenseBreakdown = $this->plService->getExpenseBreakdown($monthStart, $monthEnd);

        // Amazon sync status
        $amazonConfigured = !empty(SystemSetting::get('amazon_lwa_client_id'));
        $lastSync = Product::whereNotNull('amazon_last_synced_at')
            ->latest('amazon_last_synced_at')->first()?->amazon_last_synced_at;

        // Settlement import stats
        $recentSettlements = AmazonSettlementImport::latest()->limit(5)->get();
        $pendingSettlements = AmazonSettlementImport::where('status', 'parsed')->count();

        return view('finance.dashboard', [
            'monthSummary' => $monthSummary,
            'revenueTrend' => $revenueTrend,
            'profitTrend' => $profitTrend,
            'totalRevenue' => $totalRevenue,
            'totalProfit' => $totalProfit,
            'totalOrders' => $totalOrders,
            'totalUnits' => $totalUnits,
            'totalReturns' => $totalReturns,
            'recentSales' => $recentSales,
            'recentPOs' => $recentPOs,
            'recentExpenses' => $recentExpenses,
            'topProducts' => $topProducts,
            'monthlyTrend' => $monthlyTrend,
            'lowStock' => $lowStock,
            'pendingPOs' => $pendingPOs,
            'pendingPOValue' => $pendingPOValue,
            'poStatusCounts' => $poStatusCounts,
            'poTotalValue' => $poTotalValue,
            'poReceivedValue' => $poReceivedValue,
            'pendingReceiptUnits' => $pendingReceiptUnits,
            'inventoryValue' => $inventoryValue,
            'inventoryUnits' => $inventoryUnits,
            'productCount' => $productCount,
            'monthExpenses' => $monthExpenses,
            'expenseBreakdown' => $expenseBreakdown,
            'amazonConfigured' => $amazonConfigured,
            'lastSync' => $lastSync,
            'recentSettlements' => $recentSettlements,
            'pendingSettlements' => $pendingSettlements,
        ]);
    }

    // === Purchase Orders ===
    public function purchaseOrderIndex(Request $request)
    {
        $query = PurchaseOrder::with(['vendor:id,brand_name', 'items']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn($vq) => $vq->where('brand_name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($vendorId = $request->input('vendor_id')) {
            $query->where('vendor_id', $vendorId);
        }

        switch ($request->input('sort', 'latest')) {
            case 'oldest':
                $query->oldest();
                break;
            case 'amount':
                $query->orderByDesc('total_amount');
                break;
            case 'vendor':
                $query->join('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')->orderBy('vendors.brand_name')->select('purchase_orders.*');
                break;
            default:
                $query->latest();
        }

        $purchaseOrders = $query->paginate(25);

        $statusCounts = PurchaseOrder::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')->pluck('count', 'status')->toArray();

        $unpaidCount = PurchaseOrder::where('payment_status', 'unpaid')->count();

        return view('finance.purchase-orders', [
            'purchaseOrders' => $purchaseOrders,
            'vendors' => Vendor::orderBy('brand_name')->get(['id', 'brand_name']),
            'statusCounts' => $statusCounts,
            'unpaidCount' => $unpaidCount,
        ]);
    }

    public function purchaseOrderShow($id)
    {
        $with = ['vendor', 'items.product', 'expenses'];
        try {
            if (DB::getSchemaBuilder()->hasColumn('expenses', 'service_vendor_id')) {
                $with[] = 'expenses.serviceVendor';
            }
        } catch (\Exception $e) {
        }
        $po = PurchaseOrder::with($with)->findOrFail($id);

        // Get all sales linked to this PO
        $linkedSales = AmazonOrder::where('purchase_order_id', $po->id)
            ->with(['product:id,product_name,asin'])
            ->orderBy('order_date')
            ->get();

        // Per-item sales summary
        $itemSales = [];
        foreach ($po->items as $item) {
            $sales = $linkedSales->where('product_id', $item->product_id);
            $itemSales[$item->id] = [
                'units_sold' => $sales->whereNotIn('order_status', ['cancelled', 'returned', 'refunded'])->sum('quantity'),
                'units_remaining' => max(0, $item->quantity_received - $sales->whereNotIn('order_status', ['cancelled', 'returned', 'refunded'])->sum('quantity')),
                'revenue' => (float)$sales->whereNotIn('order_status', ['cancelled'])->sum('total_revenue'),
                'profit' => (float)$sales->whereNotIn('order_status', ['cancelled'])->sum('net_profit'),
                'orders_count' => $sales->count(),
            ];
        }

        // PO-level totals
        $poRevenue = $linkedSales->whereNotIn('order_status', ['cancelled'])->sum(fn($s) => (float)$s->total_revenue);
        $poProfit = $linkedSales->whereNotIn('order_status', ['cancelled'])->sum(fn($s) => (float)$s->net_profit);
        $poCost = (float)$po->total_amount;
        $poNetProfit = $poRevenue - $poCost;
        $poRoi = $poCost > 0 ? ($poNetProfit / $poCost) * 100 : 0;

        return view('finance.purchase-order-show', [
            'po' => $po,
            'linkedSales' => $linkedSales,
            'itemSales' => $itemSales,
            'poRevenue' => $poRevenue,
            'poProfit' => $poProfit,
            'poCost' => $poCost,
            'poNetProfit' => $poNetProfit,
            'poRoi' => $poRoi,
        ]);
    }

    public function purchaseOrderCreate()
    {
        $vendors = Vendor::orderBy('brand_name')->get(['id', 'brand_name']);
        $products = Product::with('vendor:id,brand_name')->orderBy('product_name')->get();

        $productMap = $products->mapWithKeys(function ($p) {
            return [$p->id => [
                'id' => $p->id,
                'name' => $p->product_name,
                'asin' => $p->asin ?? '',
                'upc' => $p->upc ?? '',
                'vendor_id' => $p->vendor_id,
                'vendor_name' => $p->vendor?->brand_name ?? '',
                'buying_price' => (float)$p->buying_price,
                'shipping_cost' => (float)$p->shipping_cost,
                'labeling_cost' => (float)$p->labeling_cost,
                'other_costs' => (float)$p->other_costs,
            ]];
        });

        return view('finance.purchase-order-create', [
            'vendors' => $vendors,
            'products' => $products,
            'productMap' => $productMap,
            'poNumber' => PurchaseOrder::generatePoNumber(),
        ]);
    }

    public function purchaseOrderStore(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'status' => 'required|in:draft,submitted,confirmed,in_production,shipped,received,partial_received,cancelled',
            'payment_status' => 'required|in:unpaid,partial_paid,paid,refunded',
            'payment_method' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'shipping_cost' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required|string',
            'items.*.asin' => 'nullable|string',
            'items.*.upc' => 'nullable|string',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.unit_shipping' => 'nullable|numeric|min:0',
            'items.*.unit_labeling' => 'nullable|numeric|min:0',
            'items.*.unit_other_costs' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => PurchaseOrder::generatePoNumber(),
            'vendor_id' => $validated['vendor_id'],
            'order_date' => $validated['order_date'],
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'status' => $validated['status'],
            'payment_status' => $validated['payment_status'],
            'payment_method' => $validated['payment_method'] ?? null,
            'payment_terms' => $validated['payment_terms'] ?? null,
            'shipping_cost' => $validated['shipping_cost'] ?? 0,
            'tax_amount' => $validated['tax_amount'] ?? 0,
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'amount_paid' => $validated['amount_paid'] ?? 0,
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $lineTotal = $item['quantity_ordered'] * $item['unit_cost'];
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'],
                'asin' => $item['asin'] ?? null,
                'upc' => $item['upc'] ?? null,
                'quantity_ordered' => $item['quantity_ordered'],
                'quantity_received' => 0,
                'unit_cost' => $item['unit_cost'],
                'line_total' => $lineTotal,
                'unit_shipping' => $item['unit_shipping'] ?? 0,
                'unit_labeling' => $item['unit_labeling'] ?? 0,
                'unit_other_costs' => $item['unit_other_costs'] ?? 0,
                'notes' => $item['notes'] ?? null,
            ]);
        }

        $po->recalculate();

        // Auto-generate expense if PO is created with paid/partial_paid status
        $amountPaid = (float)($validated['amount_paid'] ?? 0);
        if (in_array($validated['payment_status'], ['paid', 'partial_paid']) && $amountPaid > 0) {
            Expense::create([
                'expense_number' => Expense::generateExpenseNumber(),
                'vendor_id' => $po->vendor_id,
                'purchase_order_id' => $po->id,
                'category' => 'inventory',
                'description' => "Purchase Order {$po->po_number} payment ({$validated['payment_status']})",
                'amount' => $amountPaid,
                'expense_date' => $po->order_date,
                'status' => 'approved',
                'payment_method' => $po->payment_method,
                'notes' => 'Auto-generated from PO creation',
            ]);
        }

        $this->auditLog->log('created', 'PurchaseOrder', $po->po_number);
        return redirect()->route('finance.po.show', $po->id)->with('status', 'Purchase order created.');
    }

    public function purchaseOrderEdit($id)
    {
        $po = PurchaseOrder::with(['vendor', 'items.product'])->findOrFail($id);

        if (!in_array($po->status, ['draft', 'submitted'])) {
            return redirect()->route('finance.po.show', $po->id)
                ->with('error', 'Only draft or submitted POs can be edited.');
        }

        $vendors = Vendor::orderBy('brand_name')->get(['id', 'brand_name']);
        $products = Product::with('vendor:id,brand_name')->orderBy('product_name')->get();

        $productMap = $products->mapWithKeys(function ($p) {
            return [$p->id => [
                'id' => $p->id,
                'name' => $p->product_name,
                'asin' => $p->asin ?? '',
                'upc' => $p->upc ?? '',
                'vendor_id' => $p->vendor_id,
                'vendor_name' => $p->vendor?->brand_name ?? '',
                'buying_price' => (float)$p->buying_price,
                'shipping_cost' => (float)$p->shipping_cost,
                'labeling_cost' => (float)$p->labeling_cost,
                'other_costs' => (float)$p->other_costs,
            ]];
        });

        $existingItems = $po->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id ?? '',
                'product_name' => $item->product_name,
                'asin' => $item->asin ?? '',
                'upc' => $item->upc ?? '',
                'qty' => $item->quantity_ordered,
                'unit_cost' => (float)$item->unit_cost,
                'unit_shipping' => (float)$item->unit_shipping,
                'unit_labeling' => (float)$item->unit_labeling,
                'unit_other_costs' => (float)$item->unit_other_costs,
            ];
        });

        return view('finance.purchase-order-edit', [
            'po' => $po,
            'vendors' => $vendors,
            'products' => $products,
            'productMap' => $productMap,
            'existingItems' => $existingItems,
        ]);
    }

    public function purchaseOrderUpdate(Request $request, $id)
    {
        $po = PurchaseOrder::with('items')->findOrFail($id);

        if (!in_array($po->status, ['draft', 'submitted'])) {
            return redirect()->route('finance.po.show', $po->id)
                ->with('error', 'Only draft or submitted POs can be edited.');
        }

        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'status' => 'required|in:draft,submitted,confirmed',
            'payment_status' => 'required|in:unpaid,partial_paid,paid,refunded',
            'payment_method' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'shipping_cost' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required|string',
            'items.*.asin' => 'nullable|string',
            'items.*.upc' => 'nullable|string',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.unit_shipping' => 'nullable|numeric|min:0',
            'items.*.unit_labeling' => 'nullable|numeric|min:0',
            'items.*.unit_other_costs' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        $po->update([
            'vendor_id' => $validated['vendor_id'],
            'order_date' => $validated['order_date'],
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'status' => $validated['status'],
            'payment_status' => $validated['payment_status'],
            'payment_method' => $validated['payment_method'] ?? null,
            'payment_terms' => $validated['payment_terms'] ?? null,
            'shipping_cost' => $validated['shipping_cost'] ?? 0,
            'tax_amount' => $validated['tax_amount'] ?? 0,
            'discount_amount' => $validated['discount_amount'] ?? 0,
            'amount_paid' => $validated['amount_paid'] ?? 0,
            'notes' => $validated['notes'] ?? null,
        ]);

        $existingItemIds = $po->items->pluck('id')->toArray();
        $submittedItemIds = [];
        $receivedQtyMap = $po->items->pluck('quantity_received', 'id')->toArray();

        foreach ($validated['items'] as $itemKey => $item) {
            $lineTotal = $item['quantity_ordered'] * $item['unit_cost'];
            $itemId = is_numeric($itemKey) ? (int)$itemKey : null;

            if ($itemId && in_array($itemId, $existingItemIds)) {
                $submittedItemIds[] = $itemId;
                PurchaseOrderItem::where('id', $itemId)->update([
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'asin' => $item['asin'] ?? null,
                    'upc' => $item['upc'] ?? null,
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $lineTotal,
                    'unit_shipping' => $item['unit_shipping'] ?? 0,
                    'unit_labeling' => $item['unit_labeling'] ?? 0,
                    'unit_other_costs' => $item['unit_other_costs'] ?? 0,
                    'notes' => $item['notes'] ?? null,
                ]);
            } else {
                $newItem = PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'asin' => $item['asin'] ?? null,
                    'upc' => $item['upc'] ?? null,
                    'quantity_ordered' => $item['quantity_ordered'],
                    'quantity_received' => 0,
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $lineTotal,
                    'unit_shipping' => $item['unit_shipping'] ?? 0,
                    'unit_labeling' => $item['unit_labeling'] ?? 0,
                    'unit_other_costs' => $item['unit_other_costs'] ?? 0,
                    'notes' => $item['notes'] ?? null,
                ]);
                $submittedItemIds[] = $newItem->id;
            }
        }

        $itemsToDelete = array_diff($existingItemIds, $submittedItemIds);
        if ($itemsToDelete) {
            PurchaseOrderItem::whereIn('id', $itemsToDelete)->delete();
        }

        $po->refresh();
        $po->recalculate();

        $this->auditLog->log('updated', 'PurchaseOrder', $po->po_number);
        return redirect()->route('finance.po.show', $po->id)->with('status', 'Purchase order updated.');
    }

    public function purchaseOrderUpdateStatus(Request $request, $id)
    {
        $po = PurchaseOrder::with('items')->findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:draft,submitted,confirmed,in_production,shipped,received,partial_received,cancelled',
        ]);

        $oldStatus = $po->status;
        $po->update(['status' => $validated['status']]);

        if ($validated['status'] === 'received' && $oldStatus !== 'received') {
            $po->update(['actual_delivery_date' => now()]);

            // Auto-receive all items that haven't been received yet
            foreach ($po->items as $item) {
                $oldReceived = $item->quantity_received;
                $newReceived = $item->quantity_ordered;
                if ($newReceived > $oldReceived) {
                    $item->update(['quantity_received' => $newReceived]);
                    if ($item->product_id) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $product->updateCostsFromPO($item);
                            $product->adjustStock($newReceived - $oldReceived, 'add');
                        }
                    }
                }
            }

            $po->recalculateWithExpenses();
        }

        $this->auditLog->log('updated', 'PurchaseOrder', "{$po->po_number} status → {$validated['status']}");
        return back()->with('status', 'PO status updated.' . ($validated['status'] === 'received' && $oldStatus !== 'received' ? ' All items marked as received.' : ''));
    }

    public function purchaseOrderUpdatePayment(Request $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);
        $validated = $request->validate([
            'payment_status' => 'required|in:unpaid,partial_paid,paid,refunded',
            'amount_paid' => 'nullable|numeric|min:0',
        ]);

        $po->update($validated);

        $existingExpense = Expense::where('purchase_order_id', $po->id)->first();

        if (in_array($validated['payment_status'], ['paid', 'partial_paid'])) {
            $amountPaid = (float)($validated['amount_paid'] ?? $po->amount_paid);
            if ($amountPaid <= 0) {
                $amountPaid = (float)$po->total_amount;
                $po->update(['amount_paid' => $amountPaid]);
            }

            if ($existingExpense) {
                $existingExpense->update([
                    'amount' => $amountPaid,
                    'status' => 'approved',
                    'description' => "Purchase Order {$po->po_number} payment ({$validated['payment_status']})",
                ]);
            } else {
                Expense::create([
                    'expense_number' => Expense::generateExpenseNumber(),
                    'vendor_id' => $po->vendor_id,
                    'purchase_order_id' => $po->id,
                    'category' => 'inventory',
                    'description' => "Purchase Order {$po->po_number} payment ({$validated['payment_status']})",
                    'amount' => $amountPaid,
                    'expense_date' => $po->order_date,
                    'status' => 'approved',
                    'payment_method' => $po->payment_method,
                    'notes' => 'Auto-generated from PO payment update',
                ]);
            }
        } elseif ($existingExpense && in_array($validated['payment_status'], ['unpaid', 'refunded'])) {
            $existingExpense->update([
                'status' => 'rejected',
                'description' => "Purchase Order {$po->po_number} payment ({$validated['payment_status']}) — reversed",
            ]);
        }

        $this->auditLog->log('updated', 'PurchaseOrder', "{$po->po_number} payment → {$validated['payment_status']}");
        return back()->with('status', 'Payment status updated.');
    }

    public function purchaseOrderReceiveItem(Request $request, $id)
    {
        $request->validate([
            'item_id' => 'required|exists:purchase_order_items,id',
            'quantity_received' => 'required|integer|min:0',
        ]);

        $item = PurchaseOrderItem::where('purchase_order_id', $id)->findOrFail($request->item_id);
        $oldReceived = $item->quantity_received;
        $newReceived = $request->quantity_received;
        $item->update(['quantity_received' => $newReceived]);

        // Auto-update product costs and stock when a product is linked
        if ($item->product_id && $newReceived > $oldReceived) {
            $product = Product::find($item->product_id);
            if ($product) {
                $product->updateCostsFromPO($item);
                $product->adjustStock($newReceived - $oldReceived, 'add');
            }
        }

        $po = PurchaseOrder::with('items')->findOrFail($id);
        if ($po->is_fully_received) {
            $po->update(['status' => 'received', 'actual_delivery_date' => now()]);
        } elseif ($po->items->sum('quantity_received') > 0) {
            $po->update(['status' => 'partial_received']);
        }

        $po->recalculateWithExpenses();

        return back()->with('status', 'Received quantity updated. Product costs and stock adjusted.');
    }

    public function purchaseOrderReceiveAll($id)
    {
        $po = PurchaseOrder::with('items')->findOrFail($id);

        if ($po->status === 'cancelled') {
            return back()->with('status', 'Cannot receive items on a cancelled PO.');
        }

        if ($po->is_fully_received) {
            return back()->with('status', 'All items are already fully received.');
        }

        foreach ($po->items as $item) {
            $oldReceived = $item->quantity_received;
            $newReceived = $item->quantity_ordered;
            $item->update(['quantity_received' => $newReceived]);

            if ($item->product_id && $newReceived > $oldReceived) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->updateCostsFromPO($item);
                    $product->adjustStock($newReceived - $oldReceived, 'add');
                }
            }
        }

        $po->update(['status' => 'received', 'actual_delivery_date' => now()]);
        $po->recalculateWithExpenses();

        $this->auditLog->log('updated', 'PurchaseOrder', "{$po->po_number} → received all items");
        return back()->with('status', 'All items marked as received. Product costs and stock updated.');
    }

    // === Amazon Sales Orders ===
    public function salesIndex(Request $request)
    {
        $query = AmazonOrder::with(['product:id,product_name,asin', 'vendor:id,brand_name']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('amazon_order_id', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%")
                  ->orWhere('asin', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('order_status', $status);
        }

        if ($vendorId = $request->input('vendor_id')) {
            $query->where('vendor_id', $vendorId);
        }

        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($fulfillment = $request->input('fulfillment')) {
            $query->where('fulfillment_channel', $fulfillment);
        }

        if ($request->input('date_from')) {
            $query->where('order_date', '>=', $request->input('date_from'));
        }
        if ($request->input('date_to')) {
            $query->where('order_date', '<=', $request->input('date_to'));
        }

        switch ($request->input('sort', 'latest')) {
            case 'oldest':
                $query->oldest();
                break;
            case 'revenue':
                $query->orderByDesc('total_revenue');
                break;
            case 'profit':
                $query->orderByDesc('net_profit');
                break;
            default:
                $query->latest();
        }

        $orders = $query->paginate(25);

        $totalRevenue = AmazonOrder::whereNotIn('order_status', ['cancelled'])->sum('total_revenue');
        $totalProfit = AmazonOrder::whereNotIn('order_status', ['cancelled'])->sum('net_profit');
        $totalOrders = AmazonOrder::whereNotIn('order_status', ['cancelled'])->count();
        $totalUnits = AmazonOrder::whereNotIn('order_status', ['cancelled', 'returned', 'refunded'])->sum('quantity');

        return view('finance.sales', [
            'orders' => $orders,
            'vendors' => Vendor::orderBy('brand_name')->get(['id', 'brand_name']),
            'products' => Product::orderBy('product_name')->get(['id', 'product_name']),
            'totalRevenue' => $totalRevenue,
            'totalProfit' => $totalProfit,
            'totalOrders' => $totalOrders,
            'totalUnits' => $totalUnits,
        ]);
    }

    public function salesCreate()
    {
        $vendors = Vendor::orderBy('brand_name')->get(['id', 'brand_name']);
        $products = Product::with('vendor:id,brand_name')->orderBy('product_name')->get();
        $taxRates = TaxRate::orderBy('state_code')->get();
        $recentSales = AmazonOrder::with('product:id,product_name,asin', 'vendor:id,brand_name')
            ->latest()->limit(5)->get();

        $productMap = $products->mapWithKeys(function ($p) {
            return [$p->id => [
                'id' => $p->id,
                'name' => $p->product_name,
                'asin' => $p->asin,
                'upc' => $p->upc ?? '',
                'vendor_id' => $p->vendor_id,
                'vendor_name' => $p->vendor?->brand_name ?? '',
                'sale_price' => (float)$p->amazon_sell_price,
                'buying_price' => (float)$p->buying_price,
                'fba_fee' => (float)$p->fba_fee,
                'shipping_cost' => (float)$p->shipping_cost,
                'labeling_cost' => (float)$p->labeling_cost,
                'other_costs' => (float)$p->other_costs,
                'operation_cost' => (float)($p->operation_cost ?? 0),
                'referral_rate' => (float)$p->referral_fee_percent,
                'stock' => (int)($p->stock_quantity ?? 0),
            ]];
        });

        return view('finance.sales-create', [
            'vendors' => $vendors,
            'products' => $products,
            'taxRates' => $taxRates,
            'productMap' => $productMap,
            'recentSales' => $recentSales,
        ]);
    }

    public function salesBatchCreate()
    {
        $products = Product::with('vendor:id,brand_name')->orderBy('product_name')->get();
        $taxRates = TaxRate::orderBy('state_code')->get();

        $productMap = $products->mapWithKeys(function ($p) {
            return [$p->id => [
                'id' => $p->id,
                'name' => $p->product_name,
                'asin' => $p->asin,
                'vendor_id' => $p->vendor_id,
                'vendor_name' => $p->vendor?->brand_name ?? '',
                'sale_price' => (float)$p->amazon_sell_price,
                'buying_price' => (float)$p->buying_price,
                'fba_fee' => (float)$p->fba_fee,
                'shipping_cost' => (float)$p->shipping_cost,
                'labeling_cost' => (float)$p->labeling_cost,
                'other_costs' => (float)$p->other_costs,
                'operation_cost' => (float)($p->operation_cost ?? 0),
                'referral_rate' => (float)$p->referral_fee_percent,
                'stock' => (int)($p->stock_quantity ?? 0),
            ]];
        });

        return view('finance.sales-batch', [
            'products' => $products,
            'taxRates' => $taxRates,
            'productMap' => $productMap,
        ]);
    }

    public function salesBatchStore(Request $request)
    {
        $rows = $request->input('rows', []);
        $batchId = 'BATCH-' . now()->format('Ymd-His');

        $imported = 0;
        $errors = [];

        foreach ($rows as $idx => $row) {
            if (empty($row['product_name']) && empty($row['product_id'])) {
                continue;
            }

            try {
                $created = DB::transaction(function () use ($row, $idx, $batchId, &$errors) {
                    $product = null;
                    if (!empty($row['product_id'])) {
                        $product = Product::find($row['product_id']);
                    }

                    $productName = $row['product_name'] ?? ($product?->product_name ?? 'Unknown');
                    $asin = $row['asin'] ?? ($product?->asin ?? null);
                    $quantity = (int)($row['quantity'] ?? 1);
                    $salePrice = (float)($row['sale_price'] ?? ($product ? (float)$product->amazon_sell_price : 0));
                    $totalRevenue = $salePrice * $quantity;
                    $orderDate = $row['order_date'] ?? date('Y-m-d');
                    $fulfillment = strtoupper($row['fulfillment_channel'] ?? 'FBA');
                    if (!in_array($fulfillment, ['FBA', 'FBM'])) $fulfillment = 'FBA';

                    $productCost = (float)($row['product_cost'] ?? ($product ? (float)$product->buying_price * $quantity : 0));
                    $fbaFee = (float)($row['fba_fee'] ?? ($product ? (float)$product->fba_fee * $quantity : 0));
                    $shippingCost = (float)($row['shipping_cost'] ?? ($product ? (float)$product->shipping_cost * $quantity : 0));
                    $labelingCost = (float)($row['labeling_cost'] ?? ($product ? (float)$product->labeling_cost * $quantity : 0));
                    $otherCosts = (float)($row['other_costs'] ?? ($product ? (float)$product->other_costs * $quantity : 0));
                    $operationCost = (float)($row['operation_cost'] ?? ($product ? (float)($product->operation_cost ?? 0) * $quantity : 0));

                    $taxState = strtoupper(substr($row['tax_state'] ?? '', 0, 2));
                    $taxData = ['rate' => 0, 'amount' => 0];
                    if (!empty($taxState) && strlen($taxState) === 2) {
                        $taxData = AmazonOrder::calculateTax($totalRevenue, $taxState);
                    }

                    $amazonOrderId = $row['amazon_order_id'] ?? null;
                    if ($amazonOrderId && AmazonOrder::where('amazon_order_id', $amazonOrderId)->exists()) {
                        $errors[] = "Row " . ($idx + 1) . ": duplicate order ID {$amazonOrderId}";
                        return false;
                    }

                    $poId = null;
                    if ($product?->id) {
                        $poItem = PurchaseOrderItem::where('product_id', $product->id)
                            ->whereHas('purchaseOrder', fn($q) => $q->whereIn('status', ['confirmed', 'partial_received', 'in_production', 'shipped', 'received']))
                            ->latest()
                            ->first();
                        $poId = $poItem?->purchase_order_id;
                    }

                    $orderStatus = $row['order_status'] ?? 'delivered';

                    $order = AmazonOrder::create([
                        'amazon_order_id' => $amazonOrderId,
                        'product_id' => $product?->id,
                        'vendor_id' => $product?->vendor_id,
                        'purchase_order_id' => $poId,
                        'product_name' => $productName,
                        'asin' => $asin,
                        'fulfillment_channel' => $fulfillment,
                        'amazon_marketplace' => 'US',
                        'order_date' => $orderDate,
                        'order_status' => $orderStatus,
                        'quantity' => $quantity,
                        'sale_price' => $salePrice,
                        'total_revenue' => $totalRevenue,
                        'product_cost' => $productCost,
                        'fba_fee' => $fbaFee,
                        'amazon_referral_fee' => 0,
                        'breakaway_referral_rate' => $product ? (float)$product->referral_fee_percent : 0,
                        'shipping_cost' => $shippingCost,
                        'labeling_cost' => $labelingCost,
                        'other_costs' => $otherCosts,
                        'operation_cost' => $operationCost,
                        'tax_state' => $taxState ?: null,
                        'tax_rate' => $taxData['rate'],
                        'batch_id' => $batchId,
                    ]);

                    $order->recalculate();

                    if ($product && $this->isStockDeductingStatus($orderStatus)) {
                        $product->adjustStock($quantity, 'subtract');
                    }

                    return true;
                });

                if ($created) {
                    $imported++;
                }
            } catch (\Exception $e) {
                $errors[] = "Row " . ($idx + 1) . ": " . $e->getMessage();
            }
        }

        $msg = "Batch saved: {$imported} sales recorded (Batch ID: {$batchId})";
        if (count($errors) > 0) $msg .= ". Errors: " . implode('; ', array_slice($errors, 0, 5));

        return redirect()->route('finance.sales.index')->with('status', $msg);
    }

    public function salesStore(Request $request)
    {
        $validated = $request->validate([
            'amazon_order_id' => 'nullable|string|unique:amazon_orders,amazon_order_id',
            'product_id' => 'nullable|exists:products,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'product_name' => 'required|string',
            'asin' => 'nullable|string',
            'upc' => 'nullable|string',
            'sku' => 'nullable|string',
            'fulfillment_channel' => 'nullable|string',
            'amazon_marketplace' => 'nullable|string',
            'order_date' => 'required|date',
            'ship_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'order_status' => 'required|in:pending,processing,shipped,delivered,returned,refunded,cancelled',
            'quantity' => 'required|integer|min:1',
            'sale_price' => 'required|numeric|min:0',
            'product_cost' => 'nullable|numeric|min:0',
            'fba_fee' => 'nullable|numeric|min:0',
            'amazon_referral_fee' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'labeling_cost' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'operation_cost' => 'nullable|numeric|min:0',
            'advertising_cost' => 'nullable|numeric|min:0',
            'return_cost' => 'nullable|numeric|min:0',
            'tax_state' => 'nullable|string|size:2',
            'customer_name' => 'nullable|string',
            'customer_state' => 'nullable|string',
            'customer_city' => 'nullable|string',
            'customer_zip' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $quantity = $validated['quantity'];
        $salePrice = $validated['sale_price'];
        $totalRevenue = $salePrice * $quantity;

        $productCost = (float)($validated['product_cost'] ?? 0);
        $fbaFee = (float)($validated['fba_fee'] ?? 0);
        $shippingCost = (float)($validated['shipping_cost'] ?? 0);
        $labelingCost = (float)($validated['labeling_cost'] ?? 0);
        $otherCosts = (float)($validated['other_costs'] ?? 0);
        $operationCost = (float)($validated['operation_cost'] ?? 0);
        $advertisingCost = (float)($validated['advertising_cost'] ?? 0);
        $returnCost = (float)($validated['return_cost'] ?? 0);

        // Auto-link to latest PO for this product
        $purchaseOrderId = null;
        $referralRate = 0;
        if (!empty($validated['product_id'])) {
            $product = Product::find($validated['product_id']);
            $referralRate = (float)($product?->referral_fee_percent ?? 0);

            $latestPO = PurchaseOrderItem::where('product_id', $validated['product_id'])
                ->whereHas('purchaseOrder', fn($q) => $q->whereIn('status', ['confirmed', 'in_production', 'shipped', 'received', 'partial_received']))
                ->latest()
                ->first()?->purchase_order_id;
            if ($latestPO) {
                $purchaseOrderId = $latestPO;
            }
        }

        $taxData = ['rate' => 0, 'amount' => 0];
        if (!empty($validated['tax_state'])) {
            $taxData = AmazonOrder::calculateTax($totalRevenue, $validated['tax_state']);
        }

        $order = DB::transaction(function () use ($validated, $purchaseOrderId, $quantity, $salePrice, $totalRevenue, $productCost, $fbaFee, $shippingCost, $labelingCost, $otherCosts, $operationCost, $advertisingCost, $returnCost, $referralRate, $taxData) {
            $order = AmazonOrder::create([
                'amazon_order_id' => $validated['amazon_order_id'] ?? null,
                'product_id' => $validated['product_id'] ?? null,
                'vendor_id' => $validated['vendor_id'] ?? null,
                'purchase_order_id' => $purchaseOrderId,
                'product_name' => $validated['product_name'],
                'asin' => $validated['asin'] ?? null,
                'upc' => $validated['upc'] ?? null,
                'sku' => $validated['sku'] ?? null,
                'fulfillment_channel' => $validated['fulfillment_channel'] ?? 'FBA',
                'amazon_marketplace' => $validated['amazon_marketplace'] ?? 'US',
                'order_date' => $validated['order_date'],
                'ship_date' => $validated['ship_date'] ?? null,
                'delivery_date' => $validated['delivery_date'] ?? null,
                'order_status' => $validated['order_status'],
                'quantity' => $quantity,
                'sale_price' => $salePrice,
                'total_revenue' => $totalRevenue,
                'product_cost' => $productCost * $quantity,
                'fba_fee' => $fbaFee * $quantity,
                'amazon_referral_fee' => 0,
                'shipping_cost' => $shippingCost * $quantity,
                'labeling_cost' => $labelingCost * $quantity,
                'other_costs' => $otherCosts * $quantity,
                'operation_cost' => $operationCost * $quantity,
                'advertising_cost' => $advertisingCost * $quantity,
                'return_cost' => $returnCost * $quantity,
                'breakaway_referral_rate' => $referralRate,
                'tax_collected' => $taxData['amount'],
                'tax_rate' => $taxData['rate'],
                'tax_state' => $validated['tax_state'] ?? null,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_state' => $validated['customer_state'] ?? null,
                'customer_city' => $validated['customer_city'] ?? null,
                'customer_zip' => $validated['customer_zip'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $order->recalculate();

            if ($order->product_id && $this->isStockDeductingStatus($order->order_status)) {
                $product = Product::find($order->product_id);
                if ($product) {
                    $product->adjustStock($order->quantity, 'subtract');
                }
            }

            return $order;
        });

        $this->auditLog->log('created', 'AmazonOrder', $order->product_name);
        return redirect()->route('finance.sales.index')->with('status', 'Sales order recorded.');
    }

    public function salesUpdateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'order_status' => 'required|in:pending,processing,shipped,delivered,returned,refunded,cancelled',
        ]);

        $order = DB::transaction(function () use ($id, $validated) {
            $order = AmazonOrder::lockForUpdate()->findOrFail($id);
            $oldStatus = $order->order_status;
            $newStatus = $validated['order_status'];

            $updates = ['order_status' => $newStatus];
            if ($newStatus === 'shipped' && !$order->ship_date) {
                $updates['ship_date'] = now();
            }
            if ($newStatus === 'delivered' && !$order->delivery_date) {
                $updates['delivery_date'] = now();
            }

            $order->update($updates);

            if (in_array($newStatus, ['returned', 'refunded'])
                && !in_array($oldStatus, ['returned', 'refunded'])
                && (float)$order->return_cost <= 0
            ) {
                $returnShipping = (float)$order->shipping_cost;
                $restockingFee = (float)$order->fba_fee * 0.5;
                $order->update(['return_cost' => round($returnShipping + $restockingFee, 2)]);
            }

            $this->syncStockForStatusTransition($order, $oldStatus, $newStatus);

            $order->refresh();
            $order->recalculate();

            return $order;
        });

        $this->auditLog->log('updated', 'AmazonOrder', "Order #{$order->id} → {$validated['order_status']}");
        return back()->with('status', 'Order status updated.');
    }

    public function salesShow($id)
    {
        $order = AmazonOrder::with(['product.vendor', 'vendor', 'purchaseOrder.vendor'])->findOrFail($id);
        $expenses = Expense::where(function ($q) use ($order) {
            $q->where('product_id', $order->product_id)
              ->orWhere('vendor_id', $order->vendor_id);
        })
            ->whereBetween('expense_date', [$order->order_date->copy()->subDays(7), $order->order_date->copy()->addDays(7)])
            ->orderBy('expense_date')
            ->get();

        return view('finance.sales-show', [
            'order' => $order,
            'expenses' => $expenses,
        ]);
    }

    public function salesEdit($id)
    {
        $order = AmazonOrder::findOrFail($id);
        $vendors = Vendor::orderBy('brand_name')->get(['id', 'brand_name']);
        $products = Product::with('vendor:id,brand_name')->orderBy('product_name')->get();
        $taxRates = TaxRate::orderBy('state_code')->get();

        return view('finance.sales-edit', [
            'order' => $order,
            'vendors' => $vendors,
            'products' => $products,
            'taxRates' => $taxRates,
        ]);
    }

    public function salesUpdate(Request $request, $id)
    {
        $order = AmazonOrder::findOrFail($id);
        $validated = $request->validate([
            'amazon_order_id' => 'nullable|string|unique:amazon_orders,amazon_order_id,' . $id,
            'product_id' => 'nullable|exists:products,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'product_name' => 'required|string',
            'asin' => 'nullable|string',
            'fulfillment_channel' => 'nullable|string',
            'order_date' => 'required|date',
            'ship_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'order_status' => 'required|in:pending,processing,shipped,delivered,returned,refunded,cancelled',
            'quantity' => 'required|integer|min:1',
            'sale_price' => 'required|numeric|min:0',
            'product_cost' => 'nullable|numeric|min:0',
            'fba_fee' => 'nullable|numeric|min:0',
            'amazon_referral_fee' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'labeling_cost' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'operation_cost' => 'nullable|numeric|min:0',
            'advertising_cost' => 'nullable|numeric|min:0',
            'return_cost' => 'nullable|numeric|min:0',
            'tax_state' => 'nullable|string|size:2',
            'customer_name' => 'nullable|string',
            'customer_state' => 'nullable|string',
            'customer_city' => 'nullable|string',
            'customer_zip' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $quantity = $validated['quantity'];
        $totalRevenue = $validated['sale_price'] * $quantity;

        $referralRate = 0;
        if (!empty($validated['product_id'])) {
            $product = Product::find($validated['product_id']);
            $referralRate = (float)($product?->referral_fee_percent ?? 0);
        }

        $taxData = ['rate' => 0, 'amount' => 0];
        if (!empty($validated['tax_state'])) {
            $taxData = AmazonOrder::calculateTax($totalRevenue, $validated['tax_state']);
        }

        DB::transaction(function () use ($order, $validated, $quantity, $totalRevenue, $referralRate, $taxData) {
            $oldProductId = $order->product_id;
            $oldQuantity = (int) $order->quantity;
            $oldStatus = $order->order_status;

            $order->update([
                'amazon_order_id' => $validated['amazon_order_id'] ?? null,
                'product_id' => $validated['product_id'] ?? null,
                'vendor_id' => $validated['vendor_id'] ?? null,
                'product_name' => $validated['product_name'],
                'asin' => $validated['asin'] ?? null,
                'fulfillment_channel' => $validated['fulfillment_channel'] ?? 'FBA',
                'order_date' => $validated['order_date'],
                'ship_date' => $validated['ship_date'] ?? null,
                'delivery_date' => $validated['delivery_date'] ?? null,
                'order_status' => $validated['order_status'],
                'quantity' => $quantity,
                'sale_price' => $validated['sale_price'],
                'total_revenue' => $totalRevenue,
                'product_cost' => ($validated['product_cost'] ?? 0) * $quantity,
                'fba_fee' => ($validated['fba_fee'] ?? 0) * $quantity,
                'amazon_referral_fee' => 0,
                'breakaway_referral_rate' => $referralRate,
                'shipping_cost' => ($validated['shipping_cost'] ?? 0) * $quantity,
                'labeling_cost' => ($validated['labeling_cost'] ?? 0) * $quantity,
                'other_costs' => ($validated['other_costs'] ?? 0) * $quantity,
                'operation_cost' => ($validated['operation_cost'] ?? 0) * $quantity,
                'advertising_cost' => ($validated['advertising_cost'] ?? 0) * $quantity,
                'return_cost' => ($validated['return_cost'] ?? 0) * $quantity,
                'tax_collected' => $taxData['amount'],
                'tax_rate' => $taxData['rate'],
                'tax_state' => $validated['tax_state'] ?? null,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_state' => $validated['customer_state'] ?? null,
                'customer_city' => $validated['customer_city'] ?? null,
                'customer_zip' => $validated['customer_zip'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $order->recalculate();

            $newProductId = $order->product_id;
            $newQuantity = (int) $order->quantity;
            $newStatus = $order->order_status;
            $oldDeducts = $this->isStockDeductingStatus($oldStatus);
            $newDeducts = $this->isStockDeductingStatus($newStatus);

            if ($oldProductId && $newProductId && $oldProductId === $newProductId) {
                $product = Product::find($newProductId);
                if ($product) {
                    if ($oldDeducts && $newDeducts) {
                        $diff = $newQuantity - $oldQuantity;
                        if ($diff !== 0) {
                            $product->adjustStock(abs($diff), $diff > 0 ? 'subtract' : 'add');
                        }
                    } elseif ($oldDeducts && !$newDeducts) {
                        $product->adjustStock($oldQuantity, 'add');
                    } elseif (!$oldDeducts && $newDeducts) {
                        $product->adjustStock($newQuantity, 'subtract');
                    }
                }
            } else {
                if ($oldProductId && $oldDeducts) {
                    $oldProduct = Product::find($oldProductId);
                    if ($oldProduct) {
                        $oldProduct->adjustStock($oldQuantity, 'add');
                    }
                }

                if ($newProductId && $newDeducts) {
                    $newProduct = Product::find($newProductId);
                    if ($newProduct) {
                        $newProduct->adjustStock($newQuantity, 'subtract');
                    }
                }
            }
        });

        $this->auditLog->log('updated', 'AmazonOrder', "Order #{$order->id} edited");
        return redirect()->route('finance.sales.show', $order->id)->with('status', 'Sale updated.');
    }

    private function generateSaleExpenses(AmazonOrder $order): void
    {
        $feeMap = [
            'amazon_fees' => (float)$order->fba_fee,
            'shipping' => (float)$order->shipping_cost,
            'labeling' => (float)$order->labeling_cost,
        ];

        foreach ($feeMap as $category => $amount) {
            if ($amount > 0) {
                Expense::create([
                    'expense_number' => Expense::generateExpenseNumber(),
                    'vendor_id' => $order->vendor_id,
                    'product_id' => $order->product_id,
                    'category' => $category,
                    'description' => ucfirst(str_replace('_', ' ', $category)) . " for {$order->product_name} (Order #{$order->id})",
                    'amount' => $amount,
                    'expense_date' => $order->order_date,
                    'status' => 'approved',
                    'notes' => 'Auto-generated from Amazon sale',
                ]);
            }
        }
    }

    private function isStockDeductingStatus(?string $status): bool
    {
        return in_array((string) $status, ['pending', 'processing', 'shipped', 'delivered'], true);
    }

    private function syncStockForStatusTransition(AmazonOrder $order, ?string $oldStatus, ?string $newStatus): void
    {
        if (!$order->product_id) {
            return;
        }

        $oldDeducts = $this->isStockDeductingStatus($oldStatus);
        $newDeducts = $this->isStockDeductingStatus($newStatus);

        if ($oldDeducts === $newDeducts) {
            return;
        }

        $product = Product::find($order->product_id);
        if (!$product) {
            return;
        }

        $product->adjustStock($order->quantity, $newDeducts ? 'subtract' : 'add');
    }

    // === Expenses ===
    public function expenseIndex(Request $request)
    {
        $with = ['vendor:id,brand_name', 'product:id,product_name', 'purchaseOrder:id,po_number'];
        try {
            if (DB::getSchemaBuilder()->hasColumn('expenses', 'service_vendor_id')) {
                $with[] = 'serviceVendor:id,brand_name';
            }
        } catch (\Exception $e) {
        }
        $query = Expense::with($with);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('expense_number', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($vendorId = $request->input('vendor_id')) {
            $query->where('vendor_id', $vendorId);
        }

        if ($request->input('date_from')) {
            $query->where('expense_date', '>=', $request->input('date_from'));
        }
        if ($request->input('date_to')) {
            $query->where('expense_date', '<=', $request->input('date_to'));
        }

        $expenses = $query->latest('expense_date')->paginate(25);

        $totalExpenses = Expense::where('status', '!=', 'rejected')->sum('amount');
        $pendingExpenses = Expense::where('status', 'pending')->sum('amount');
        $categoryTotals = Expense::where('status', '!=', 'rejected')
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')->pluck('total', 'category')->toArray();

        return view('finance.expenses', [
            'expenses' => $expenses,
            'vendors' => Vendor::orderBy('brand_name')->get(['id', 'brand_name']),
            'purchaseOrders' => PurchaseOrder::with('vendor:id,brand_name')
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->orderByDesc('id')
                ->limit(100)
                ->get(['id', 'po_number', 'vendor_id', 'status', 'total_amount']),
            'totalExpenses' => $totalExpenses,
            'pendingExpenses' => $pendingExpenses,
            'categoryTotals' => $categoryTotals,
            'categoryLabels' => Expense::categoryLabels(),
            'categoryColors' => Expense::categoryColors(),
        ]);
    }

    public function expenseStore(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'nullable|exists:vendors,id',
            'service_vendor_id' => 'nullable|exists:vendors,id',
            'product_id' => 'nullable|exists:products,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'category' => 'required|in:shipping,labeling,inventory,amazon_fees,fba_fees,amazon_referral,prep,inspection,customs,freight,insurance,advertising,storage,returns,supplies,software,fees,other',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'status' => 'required|in:pending,approved,paid,rejected',
            'allocation_method' => 'nullable|in:none,by_quantity,by_value,specific',
            'payment_method' => 'nullable|string',
            'vendor_name' => 'nullable|string',
            'receipt_url' => 'nullable|string',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['expense_number'] = Expense::generateExpenseNumber();
        $validated['is_recurring'] = $request->boolean('is_recurring');

        // Allocation method only applies when a PO is linked
        if (empty($validated['purchase_order_id'])) {
            $validated['allocation_method'] = 'none';
        } else {
            $validated['allocation_method'] = $validated['allocation_method'] ?? 'by_quantity';
        }

        // Remove new columns if migration hasn't been run yet (pre-migration safety)
        $hasNewColumns = cache()->remember('expenses_has_landed_cost_columns', 300, function () {
            try {
                return DB::getSchemaBuilder()->hasColumn('expenses', 'service_vendor_id');
            } catch (\Exception $e) {
                return false;
            }
        });
        if (!$hasNewColumns) {
            unset($validated['service_vendor_id'], $validated['allocation_method']);
        }

        $expense = Expense::create($validated);
        $this->auditLog->log('created', 'Expense', $expense->expense_number);

        // Trigger PO recalculation if expense is linked to a PO and already approved/paid
        if ($expense->purchase_order_id && in_array($expense->status, ['approved', 'paid'])) {
            $po = PurchaseOrder::with('items')->find($expense->purchase_order_id);
            if ($po) {
                $po->recalculateWithExpenses();
            }
        }

        return back()->with('status', 'Expense recorded.');
    }

    public function expenseUpdateStatus(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,paid,rejected',
        ]);

        $oldStatus = $expense->status;
        $expense->update($validated);
        $this->auditLog->log('updated', 'Expense', "{$expense->expense_number} → {$validated['status']}");

        // Trigger PO landed cost recalculation when status changes to/from approved/paid
        if ($expense->purchase_order_id) {
            $wasAllocated = in_array($oldStatus, ['approved', 'paid']);
            $isAllocated = in_array($validated['status'], ['approved', 'paid']);
            if ($wasAllocated !== $isAllocated) {
                $po = PurchaseOrder::with('items')->find($expense->purchase_order_id);
                if ($po) {
                    $po->recalculateWithExpenses();
                }
            }
        }

        return back()->with('status', 'Expense status updated.');
    }

    // === Profit & Loss Report ===
    public function profitLossReport(Request $request)
    {
        $period = $request->input('period', 'month');
        $endDate = Carbon::now();

        switch ($period) {
            case 'week':
                $startDate = Carbon::now()->startOfWeek();
                break;
            case 'quarter':
                $startDate = Carbon::now()->startOfQuarter();
                break;
            case 'year':
                $startDate = Carbon::now()->startOfYear();
                break;
            case 'all':
                $startDate = Carbon::create(2000, 1, 1);
                break;
            default:
                $startDate = Carbon::now()->startOfMonth();
        }

        if ($request->input('date_from')) {
            $startDate = Carbon::parse($request->input('date_from'));
        }
        if ($request->input('date_to')) {
            $endDate = Carbon::parse($request->input('date_to'));
        }

        $summary = $this->plService->getOverallSummary($startDate, $endDate);
        $vendorBreakdown = $this->plService->getPerVendorBreakdown($startDate, $endDate);
        $productBreakdown = $this->plService->getPerProductBreakdown($startDate, $endDate);
        $expenseBreakdown = $this->plService->getExpenseBreakdown($startDate, $endDate);
        $monthlyTrend = $this->plService->getMonthlyTrend(12);

        return view('finance.profit-loss', [
            'summary' => $summary,
            'vendorBreakdown' => $vendorBreakdown,
            'productBreakdown' => $productBreakdown,
            'expenseBreakdown' => $expenseBreakdown,
            'monthlyTrend' => $monthlyTrend,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'period' => $period,
        ]);
    }

    // === Order Tracking ===
    public function orderTracking(Request $request)
    {
        $query = AmazonOrder::with(['product:id,product_name,asin', 'vendor:id,brand_name'])
            ->whereIn('order_status', ['pending', 'processing', 'shipped']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('amazon_order_id', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('order_status', $status);
        }

        $orders = $query->orderBy('order_date')->paginate(25);

        $statusCounts = AmazonOrder::whereIn('order_status', ['pending', 'processing', 'shipped'])
            ->selectRaw('order_status, COUNT(*) as count')
            ->groupBy('order_status')->pluck('count', 'order_status')->toArray();

        // Active purchase orders (not draft, received, or cancelled)
        $poQuery = PurchaseOrder::with(['vendor:id,brand_name', 'items'])
            ->whereNotIn('status', ['draft', 'received', 'cancelled']);

        if ($search = $request->input('search')) {
            $poQuery->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhereHas('items', fn($iq) => $iq->where('product_name', 'like', "%{$search}%"));
            });
        }

        $purchaseOrders = $poQuery->orderBy('order_date')->get();

        $poStatusCounts = PurchaseOrder::whereNotIn('status', ['draft', 'received', 'cancelled'])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')->pluck('count', 'status')->toArray();

        return view('finance.order-tracking', [
            'orders' => $orders,
            'statusCounts' => $statusCounts,
            'purchaseOrders' => $purchaseOrders,
            'poStatusCounts' => $poStatusCounts,
        ]);
    }

    // === Tax Rates ===
    public function taxRates()
    {
        $rates = TaxRate::orderBy('state_code')->get();
        return view('finance.tax-rates', ['rates' => $rates]);
    }

    public function seedTaxRates()
    {
        $data = TaxRate::seedUsStates();
        foreach ($data as $row) {
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

        return back()->with('status', count($data) . ' US state tax rates seeded.');
    }

    public function salesImportCsv()
    {
        $products = Product::orderBy('product_name')->get(['id', 'product_name', 'asin']);
        return view('finance.sales-import-csv', ['products' => $products]);
    }

    public function salesImportCsvStore(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        // Normalize header keys
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);

            // Skip empty rows
            if (empty(array_filter($data))) { $skipped++; continue; }

            // Map CSV columns to fields
            $productName = $data['product_name'] ?? $data['title'] ?? '';
            $asin = $data['asin'] ?? '';
            $amazonOrderId = $data['amazon_order_id'] ?? $data['order_id'] ?? '';
            $orderDate = $data['order_date'] ?? $data['date'] ?? date('Y-m-d');
            $quantity = (int)($data['quantity'] ?? $data['qty'] ?? 1);
            $salePrice = (float)($data['sale_price'] ?? $data['price'] ?? $data['item_price'] ?? 0);
            $fulfillment = strtoupper($data['fulfillment'] ?? $data['fulfillment_channel'] ?? 'FBA');
            $orderStatus = strtolower($data['status'] ?? $data['order_status'] ?? 'delivered');
            $productCost = (float)($data['product_cost'] ?? $data['cost'] ?? 0);
            $fbaFee = (float)($data['fba_fee'] ?? $data['amazon_fee'] ?? 0);
            $shippingCost = (float)($data['shipping_cost'] ?? $data['shipping'] ?? 0);
            $taxState = strtoupper(substr($data['tax_state'] ?? $data['state'] ?? '', 0, 2));

            if (empty($productName)) { $skipped++; $errors[] = 'Row ' . ($imported + $skipped + 1) . ': missing product name'; continue; }

            // Try to find product by ASIN or name
            $product = null;
            if ($asin) {
                $product = Product::where('asin', $asin)->first();
            }
            if (!$product && $productName) {
                $product = Product::where('product_name', 'like', "%{$productName}%")->first();
            }

            $totalRevenue = $salePrice * $quantity;

            $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'returned', 'refunded', 'cancelled'];
            if (!in_array($orderStatus, $validStatuses)) $orderStatus = 'delivered';
            if (!in_array($fulfillment, ['FBA', 'FBM'])) $fulfillment = 'FBA';

            $taxData = ['rate' => 0, 'amount' => 0];
            if (!empty($taxState) && strlen($taxState) === 2) {
                $taxData = AmazonOrder::calculateTax($totalRevenue, $taxState);
            }

            // Check for duplicate order ID
            if ($amazonOrderId && AmazonOrder::where('amazon_order_id', $amazonOrderId)->exists()) {
                $skipped++;
                $errors[] = 'Row ' . ($imported + $skipped + 1) . ': duplicate order ID ' . $amazonOrderId;
                continue;
            }

            $order = AmazonOrder::create([
                'amazon_order_id' => $amazonOrderId ?: null,
                'product_id' => $product?->id,
                'vendor_id' => $product?->vendor_id,
                'product_name' => $productName,
                'asin' => $asin ?: ($product?->asin),
                'fulfillment_channel' => $fulfillment,
                'amazon_marketplace' => 'US',
                'order_date' => $orderDate,
                'order_status' => $orderStatus,
                'quantity' => $quantity,
                'sale_price' => $salePrice,
                'product_cost' => $productCost ?: ($product ? (float)$product->buying_price : 0),
                'fba_fee' => $fbaFee ?: ($product ? (float)$product->fba_fee : 0),
                'amazon_referral_fee' => 0,
                'breakaway_referral_rate' => $product ? (float)$product->referral_fee_percent : 0,
                'shipping_cost' => $shippingCost,
                'total_revenue' => $totalRevenue,
                'tax_state' => $taxState ?: null,
                'tax_rate' => $taxData['rate'],
                'tax_collected' => $taxData['amount'],
            ]);

            $order->recalculate();

            // Auto-deduct stock
            if ($product) {
                $product->adjustStock($quantity, 'subtract');
            }

            $imported++;
        }

        fclose($handle);

        $msg = "Imported {$imported} sales";
        if ($skipped > 0) $msg .= ", skipped {$skipped}";
        if (count($errors) > 0) $msg .= ". Errors: " . implode('; ', array_slice($errors, 0, 5));

        return redirect()->route('finance.sales.index')->with('status', $msg);
    }

    // === Amazon SP-API Settings & Sync ===

    public function amazonSettings()
    {
        $settings = [
            'lwa_client_id' => SystemSetting::get('amazon_lwa_client_id'),
            'lwa_client_secret' => SystemSetting::get('amazon_lwa_client_secret'),
            'refresh_token' => SystemSetting::get('amazon_refresh_token'),
            'sp_api_access_key' => SystemSetting::get('amazon_sp_api_access_key'),
            'sp_api_secret_key' => SystemSetting::get('amazon_sp_api_secret_key'),
            'marketplace_id' => SystemSetting::get('amazon_marketplace_id', 'ATVPDKIKX0DER'),
            'aws_region' => SystemSetting::get('amazon_aws_region', 'us-east-1'),
            'sp_api_endpoint' => SystemSetting::get('amazon_sp_api_endpoint', 'https://sellingpartnerapi-na.amazon.com'),
        ];

        $api = new AmazonSpApiService();
        $configured = $api->isConfigured();

        return view('settings.amazon', ['settings' => $settings, 'configured' => $configured]);
    }

    public function amazonSettingsSave(Request $request)
    {
        $validated = $request->validate([
            'lwa_client_id' => 'nullable|string',
            'lwa_client_secret' => 'nullable|string',
            'refresh_token' => 'nullable|string',
            'sp_api_access_key' => 'nullable|string',
            'sp_api_secret_key' => 'nullable|string',
            'marketplace_id' => 'nullable|string',
            'aws_region' => 'nullable|string|in:us-east-1,eu-west-1,us-west-2',
            'sp_api_endpoint' => 'nullable|string|url',
        ]);

        foreach ($validated as $key => $value) {
            if (empty($value)) continue;

            $settingKey = 'amazon_' . $key;
            $encrypt = in_array($key, ['lwa_client_secret', 'refresh_token', 'sp_api_secret_key']);

            SystemSetting::set($settingKey, $value, 'amazon', $encrypt);
        }

        SystemSetting::flushCache();

        return redirect()->route('settings.amazon')->with('status', 'Amazon SP-API credentials saved.');
    }

    public function amazonDisconnect()
    {
        $keys = [
            'amazon_lwa_client_id', 'amazon_lwa_client_secret', 'amazon_refresh_token',
            'amazon_sp_api_access_key', 'amazon_sp_api_secret_key',
        ];

        foreach ($keys as $key) {
            $setting = SystemSetting::where('key', $key)->first();
            $setting?->delete();
        }

        SystemSetting::flushCache();

        return redirect()->route('settings.amazon')->with('status', 'Disconnected from Amazon SP-API.');
    }

    public function amazonSync(Request $request)
    {
        $syncService = new AmazonSyncService(new AmazonSpApiService());

        if (!$syncService->isConfigured()) {
            return back()->with('error', 'Amazon SP-API is not configured.');
        }

        $type = $request->input('type', 'full');

        try {
            switch ($type) {
                case 'products':
                    $products = Product::whereNotNull('asin')->where('asin', '!=', '')->get();
                    $synced = 0;
                    $errors = 0;
                    foreach ($products as $product) {
                        $result = $syncService->syncProduct($product);
                        $result['success'] ? $synced++ : $errors++;
                    }
                    return back()->with('status', "Product sync complete: {$synced} synced, {$errors} errors.");

                case 'orders':
                    $result = $syncService->syncOrders(now()->subDays(7), now());
                    return back()->with($result['success'] ? 'status' : 'error', $result['message'] ?? $result['error']);

                case 'inventory':
                    $result = $syncService->syncInventory();
                    return back()->with($result['success'] ? 'status' : 'error', $result['message'] ?? $result['error']);

                default:
                    $results = $syncService->fullSync();
                    $msg = "Full sync complete. ";
                    $msg .= "Products: {$results['products']['synced']} synced, {$results['products']['errors']} errors. ";
                    $msg .= "Orders: " . ($results['orders']['message'] ?? 'N/A') . ". ";
                    $msg .= "Inventory: " . ($results['inventory']['message'] ?? 'N/A');
                    return back()->with('status', $msg);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    // === Amazon Financial Statement Import ===

    public function refreshPnlCache(Request $request)
    {
        $months = (int)($request->input('months', 12));
        $this->plService->cacheMonthlySummaries($months);

        $this->auditLog->log('updated', 'ProfitLossSummary', "Refreshed P&L cache for {$months} months");

        return back()->with('status', "P&L cache refreshed for {$months} months.");
    }

    public function aiAnalysis(Request $request)
    {
        $startDate = $request->input('date_from') ? Carbon::parse($request->input('date_from')) : Carbon::now()->startOfMonth();
        $endDate = $request->input('date_to') ? Carbon::parse($request->input('date_to')) : Carbon::now();

        $aiService = app(\App\Services\AI\AiFinancialAnalysisService::class);

        $anomalies = $aiService->detectAnomalies($startDate, $endDate);

        $narrative = null;
        $narrativeError = null;

        if ($request->input('generate_narrative')) {
            $result = $aiService->generateMonthlyNarrative($startDate, $endDate);
            if ($result['success']) {
                $narrative = $result['narrative'];
            } else {
                $narrativeError = $result['error'] ?? 'Failed to generate narrative';
            }
        }

        return view('finance.ai-analysis', [
            'anomalies' => $anomalies,
            'narrative' => $narrative,
            'narrativeError' => $narrativeError,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function aiCategorizeExpense(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
            'amount' => 'nullable|numeric',
            'vendor_name' => 'nullable|string',
        ]);

        $aiService = app(\App\Services\AI\AiFinancialAnalysisService::class);

        $result = $aiService->categorizeExpense(
            $request->input('description'),
            $request->input('amount') ? (float)$request->input('amount') : null,
            $request->input('vendor_name')
        );

        return response()->json($result);
    }

    public function settlementIndex()
    {
        $imports = AmazonSettlementImport::withCount([
            'transactions',
            'transactions as matched_orders_count' => fn($q) => $q->where('match_status', 'matched_order'),
            'transactions as duplicates_count' => fn($q) => $q->where('match_status', 'duplicate'),
            'transactions as unmatched_count' => fn($q) => $q->where('match_status', 'unmatched'),
        ])->latest()->paginate(20);

        return view('finance.settlements-index', ['imports' => $imports]);
    }

    public function settlementUpload()
    {
        return view('finance.settlements-upload');
    }

    public function settlementStore(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,tsv,xml|max:10240',
        ]);

        try {
            $import = $this->importService->parseFile(
                $request->file('file')->getRealPath(),
                $request->file('file')->getClientOriginalName(),
                $request->user()->id
            );

            $this->auditLog->log('created', 'AmazonSettlementImport', "Imported settlement file: {$import->file_name}");

            return redirect()
                ->route('finance.settlements.show', $import->id)
                ->with('status', "File parsed successfully. {$import->transactions->count()} transactions found. Review and confirm to import.");

        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage())->withInput();
        }
    }

    public function settlementShow($id)
    {
        $import = AmazonSettlementImport::with(['transactions' => fn($q) => $q->latest()])->findOrFail($id);

        $stats = [
            'total' => $import->transactions->count(),
            'matched_orders' => $import->transactions->where('match_status', 'matched_order')->count(),
            'matched_products' => $import->transactions->where('match_status', 'matched_product')->count(),
            'matched_vendors' => $import->transactions->where('match_status', 'matched_vendor')->count(),
            'unmatched' => $import->transactions->where('match_status', 'unmatched')->count(),
            'duplicates' => $import->transactions->where('match_status', 'duplicate')->count(),
            'fees' => $import->transactions->whereIn('transaction_type', ['fee', 'service_fee', 'storage_fee', 'advertising'])->count(),
            'orders' => $import->transactions->where('transaction_type', 'order')->count(),
            'refunds' => $import->transactions->where('transaction_type', 'refund')->count(),
            'total_fees' => $import->transactions->whereIn('transaction_type', ['fee', 'service_fee', 'storage_fee', 'advertising'])->sum(fn($t) => abs((float)$t->amount)),
            'total_revenue' => $import->transactions->where('transaction_type', 'order')->sum(fn($t) => (float)$t->amount),
            'total_refunds' => $import->transactions->where('transaction_type', 'refund')->sum(fn($t) => abs((float)$t->amount)),
        ];

        return view('finance.settlements-show', [
            'import' => $import,
            'stats' => $stats,
        ]);
    }

    public function settlementCommit($id)
    {
        $import = AmazonSettlementImport::findOrFail($id);

        if ($import->status === 'imported') {
            return back()->with('error', 'This settlement has already been imported.');
        }

        $stats = $this->importService->commitImport($import);

        $this->auditLog->log('updated', 'AmazonSettlementImport', "Settlement import #{$import->id} committed: {$stats['expenses_created']} expenses, {$stats['orders_updated']} orders updated");

        $msg = "Import complete: {$stats['expenses_created']} expenses created, {$stats['orders_updated']} orders updated";
        if ($stats['duplicates_skipped'] > 0) $msg .= ", {$stats['duplicates_skipped']} duplicates skipped";
        if ($stats['unmatched_skipped'] > 0) $msg .= ", {$stats['unmatched_skipped']} unmatched skipped";
        if (!empty($stats['errors'])) $msg .= ". Errors: " . implode('; ', array_slice($stats['errors'], 0, 3));

        return redirect()->route('finance.settlements.index')->with('status', $msg);
    }

    public function settlementDestroy($id)
    {
        $import = AmazonSettlementImport::findOrFail($id);

        if ($import->status === 'imported') {
            Expense::where('metadata->amazon_settlement_import_id', $import->id)->delete();
        }

        $import->delete();
        $this->auditLog->log('deleted', 'AmazonSettlementImport', "Deleted settlement import #{$id}");

        return redirect()->route('finance.settlements.index')->with('status', 'Settlement import deleted.');
    }

    public function settlementReconciliation(Request $request)
    {
        $imports = AmazonSettlementImport::where('status', 'imported')
            ->with(['transactions' => fn($q) => $q->whereIn('transaction_type', ['order', 'refund'])])
            ->latest()->limit(10)->get();

        $reconciliation = [];
        foreach ($imports as $import) {
            $settlementRevenue = $import->transactions->where('transaction_type', 'order')->sum(fn($t) => (float)$t->amount);
            $settlementRefunds = $import->transactions->where('transaction_type', 'refund')->sum(fn($t) => abs((float)$t->amount));
            $settlementFees = $import->transactions->whereIn('transaction_type', ['fee', 'service_fee', 'storage_fee', 'advertising'])->sum(fn($t) => abs((float)$t->amount));

            $matchedOrderIds = $import->transactions->where('match_status', 'matched_order')->whereNotNull('amazon_order_id')->pluck('amazon_order_id');
            $recordedRevenue = AmazonOrder::whereIn('id', $matchedOrderIds)->whereNotIn('order_status', ['cancelled', 'returned', 'refunded'])->sum('total_revenue');
            $recordedFees = Expense::where('metadata->amazon_settlement_import_id', $import->id)->sum('amount');

            $reconciliation[] = [
                'import' => $import,
                'settlement_revenue' => round($settlementRevenue, 2),
                'settlement_refunds' => round($settlementRefunds, 2),
                'settlement_fees' => round($settlementFees, 2),
                'settlement_net' => round($settlementRevenue - $settlementRefunds - $settlementFees, 2),
                'recorded_revenue' => round((float)$recordedRevenue, 2),
                'recorded_fees' => round((float)$recordedFees, 2),
                'revenue_diff' => round($settlementRevenue - (float)$recordedRevenue, 2),
                'fees_diff' => round($settlementFees - (float)$recordedFees, 2),
            ];
        }

        return view('finance.settlements-reconciliation', ['reconciliation' => $reconciliation]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Campaign;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\AuditLogService;
use App\Support\CategoryOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function __construct(private AuditLogService $auditLog)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::query()->with('vendor:id,brand_name,company_name,contact_email,status');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', '%' . $search . '%')
                    ->orWhere('asin', 'like', '%' . $search . '%')
                    ->orWhereHas('vendor', fn($vq) => $vq->where('brand_name', 'like', '%' . $search . '%'));
            });
        }

        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->input('category')) {
            $query->where('product_category', $request->input('category'));
        }

        if ($request->input('vendor_id')) {
            $query->where('vendor_id', $request->input('vendor_id'));
        }

        $sort = $request->input('sort', 'latest');
        switch ($sort) {
            case 'margin':
                $query->orderByDesc('margin_percent');
                break;
            case 'profit':
                $query->orderByDesc('net_profit');
                break;
            case 'roi':
                $query->orderByDesc('roi_percent');
                break;
            case 'price':
                $query->orderByDesc('amazon_sell_price');
                break;
            case 'name':
                $query->orderBy('product_name');
                break;
            default:
                $query->latest();
        }

        $products = $query->paginate(25);

        return view('products.index', [
            'products' => $products,
            'vendors' => Vendor::orderBy('brand_name')->get(['id', 'brand_name']),
            'categories' => CategoryOptions::categories(),
        ]);
    }

    public function show($id)
    {
        $product = Product::with('vendor:id,brand_name,company_name,contact_email,status')->findOrFail($id);
        $this->authorize('view', $product);

        return view('products.show', [
            'product' => $product,
            'categories' => CategoryOptions::categories(),
        ]);
    }

    public function storeForVendor(ProductRequest $request, $vendorId)
    {
        $this->authorize('create', Product::class);

        $vendor = Vendor::findOrFail($vendorId);
        $validated = $this->applyNumericDefaults($request->validated());
        $validated['vendor_id'] = $vendor->id;

        try {
            $product = Product::create($validated);
            $product->recalculate();
        } catch (\Exception $e) {
            Log::error('Failed to create product', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);
            return back()->with('error', 'Failed to create product: ' . $e->getMessage())->withInput();
        }

        $this->auditLog->log('created', 'Product', $product->product_name);

        return back()->with('status', 'Product added.');
    }

    public function store(ProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $validated = $request->validated();
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
        ]);

        $validated['vendor_id'] = $request->input('vendor_id');
        $validated = $this->applyNumericDefaults($validated);

        if (!empty($validated['asin'])) {
            $existing = Product::where('asin', $validated['asin'])->first();
            if ($existing) {
                return back()->with('error', "A product with ASIN {$validated['asin']} already exists: {$existing->product_name}")->withInput();
            }
        }

        try {
            $product = Product::create($validated);
            $product->recalculate();
        } catch (\Exception $e) {
            Log::error('Failed to create product', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);
            return back()->with('error', 'Failed to create product: ' . $e->getMessage())->withInput();
        }

        $this->auditLog->log('created', 'Product', $product->product_name);

        if ($request->has('add_another')) {
            return back()->with('status', "Product '{$product->product_name}' added. Add another below.");
        }

        return redirect()->route('products.show', $product->id)->with('status', 'Product added.');
    }

    public function update(ProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);

        $validated = $this->applyNumericDefaults($request->validated());

        try {
            $product->update($validated);
            $product->recalculate();
        } catch (\Exception $e) {
            Log::error('Failed to update product', [
                'error' => $e->getMessage(),
                'product_id' => $id,
            ]);

            return back()->with('error', 'Failed to update product: ' . $e->getMessage())->withInput();
        }

        $this->auditLog->log('updated', 'Product', $product->product_name);

        return back()->with('status', 'Product updated.');
    }

    public function destroy(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $this->authorize('delete', $product);

        $product->delete();
        $this->auditLog->log('deleted', 'Product', $product->product_name);

        return back()->with('status', 'Product deleted.');
    }

    public function bulkAction(Request $request)
    {
        $this->authorize('bulk', Product::class);

        $productIds = $request->input('product_ids');
        if (is_string($productIds)) {
            $productIds = array_filter(explode(',', $productIds));
        }

        $request->merge(['product_ids' => $productIds]);

        $request->validate([
            'action' => 'required|in:set_status,delete,recalculate',
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'status' => 'required_if:action,set_status|in:active,inactive,discontinued',
        ]);

        $products = Product::whereIn('id', $productIds)->get();
        $count = $products->count();

        if ($count === 0) {
            return back()->with('error', 'No products selected.');
        }

        switch ($request->input('action')) {
            case 'set_status':
                $status = $request->input('status');
                Product::whereIn('id', $productIds)->update(['status' => $status]);
                $this->auditLog->log('bulk_updated', 'Products', "Set {$count} products to {$status}");
                return back()->with('status', "{$count} products set to {$status}.");

            case 'delete':
                Product::whereIn('id', $productIds)->delete();
                $this->auditLog->log('bulk_deleted', 'Products', "Deleted {$count} products");
                return back()->with('status', "{$count} products deleted.");

            case 'recalculate':
                foreach ($products as $product) {
                    $product->recalculate();
                }
                $this->auditLog->log('bulk_recalculated', 'Products', "Recalculated {$count} products");
                return back()->with('status', "{$count} products recalculated.");
        }

        return back()->with('error', 'Invalid action.');
    }

    public function exportByVendor($vendorId)
    {
        $this->authorize('export', Product::class);

        $vendor = Vendor::findOrFail($vendorId);
        $products = $vendor->products;
        $filename = "products_{$vendor->brand_name}_" . now()->format('Y_m_d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = ['Product Name', 'ASIN', 'Category', 'Buying Price', 'FBA Fee', 'Shipping', 'Labeling', 'Other', 'Total Cost', 'Sell Price', 'Amazon Fee', 'Net Profit', 'Margin %', 'ROI %', 'Sellers', 'Buy Box', 'BSR', 'Reviews', 'Rating'];

        $callback = function () use ($products, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($products as $product) {
                fputcsv($file, [
                    $product->product_name,
                    $product->asin,
                    $product->product_category,
                    $product->buying_price,
                    $product->fba_fee,
                    $product->shipping_cost,
                    $product->labeling_cost,
                    $product->other_costs,
                    $product->total_cost,
                    $product->amazon_sell_price,
                    $product->amazon_fee,
                    $product->net_profit,
                    $product->margin_percent,
                    $product->roi_percent,
                    $product->number_of_sellers,
                    $product->buy_box_type,
                    $product->bsr_rank,
                    $product->review_count,
                    $product->review_rating,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function applyNumericDefaults(array $validated): array
    {
        $numericDefaults = [
            'buying_price' => 0,
            'fba_fee' => 0,
            'shipping_cost' => 0,
            'labeling_cost' => 0,
            'other_costs' => 0,
            'operation_cost' => 0,
            'amazon_sell_price' => 0,
            'referral_fee_percent' => 0,
            'number_of_sellers' => 0,
            'bsr_rank' => null,
            'review_count' => null,
            'review_rating' => null,
        ];

        foreach ($numericDefaults as $field => $default) {
            if (!isset($validated[$field]) || $validated[$field] === '') {
                $validated[$field] = $default;
            }
        }

        $textFields = ['asin', 'sku', 'upc', 'image_url', 'notes', 'product_category', 'buy_box_type'];
        foreach ($textFields as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] === null) {
                $validated[$field] = '';
            }
        }

        return $validated;
    }
}

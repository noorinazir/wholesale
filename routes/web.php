<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Settings\AuditLogController;
use App\Http\Controllers\Settings\CompanyDocumentController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\VendorBrandApprovalController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorDocumentResponseController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'check'])->name('health');

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [ApplicationController::class, 'dashboard'])->name('dashboard');
    Route::view('profile', 'profile')->name('profile');

    // Vendors (read: view-vendors/manage-vendors, write: manage-vendors)
    Route::get('vendors', [VendorController::class, 'index'])->name('vendors.index')->middleware('can:view-vendors');
    Route::view('vendors/create', 'vendors.create')->name('vendors.create')->middleware('can:manage-vendors');
    Route::view('vendors/import', 'vendors.import')->name('vendors.import')->middleware('can:manage-vendors');
    Route::get('vendors/{id}', [VendorController::class, 'show'])->name('vendors.show')->middleware('can:view-vendors');
    Route::view('vendors/{id}/edit', 'vendors.edit')->name('vendors.edit')->middleware('can:manage-vendors');
    Route::post('vendors/create', [VendorController::class, 'store'])->middleware('can:manage-vendors');
    Route::post('vendors/import', [VendorController::class, 'import'])->middleware('can:manage-vendors');
    Route::post('vendors/{id}', [VendorController::class, 'action'])->middleware('can:manage-vendors');
    Route::put('vendors/{id}/edit', [VendorController::class, 'update'])->middleware('can:manage-vendors');
    Route::post('vendors/{id}/status', [VendorController::class, 'updateStatus'])->name('vendors.status')->middleware('can:manage-vendors');
    Route::post('vendors/{id}/priority', [VendorController::class, 'updatePriority'])->name('vendors.priority')->middleware('can:manage-vendors');
    Route::post('vendors/{id}/generate-document-response', [VendorDocumentResponseController::class, 'generate'])->name('vendors.generate-document-response')->middleware('can:manage-vendors');
    Route::delete('vendors/{id}', [VendorController::class, 'destroy'])->name('vendors.destroy')->middleware('can:manage-vendors');

    // Suppression List (read: view-vendors/manage-vendors, write: manage-vendors)
    Route::view('suppression', 'suppression.index')->name('suppression.index')->middleware('can:view-vendors');

    // Campaigns (read: view-campaigns/manage-campaigns, write: manage-campaigns)
    Route::view('campaigns', 'campaigns.index')->name('campaigns.index')->middleware('can:view-campaigns');
    Route::view('campaigns/create', 'campaigns.create')->name('campaigns.create')->middleware('can:manage-campaigns');
    Route::view('campaigns/{id}', 'campaigns.show')->name('campaigns.show')->middleware('can:view-campaigns');
    Route::post('campaigns/create', [CampaignController::class, 'store'])->middleware('can:manage-campaigns');
    Route::post('campaigns/{id}/add-vendors', [CampaignController::class, 'addVendors'])->middleware('can:manage-campaigns');
    Route::post('campaigns/{id}/remove-vendor', [CampaignController::class, 'removeVendor'])->middleware('can:manage-campaigns');
    Route::post('campaigns/{id}/generate-emails', [CampaignController::class, 'bulkGenerateEmails'])->middleware('can:manage-campaigns');
    Route::post('campaigns/{id}/start', [CampaignController::class, 'start'])->middleware('can:manage-campaigns');
    Route::post('campaigns/{id}/pause', [CampaignController::class, 'pause'])->middleware('can:manage-campaigns');
    Route::put('campaigns/{id}/update-automation', [CampaignController::class, 'updateAutomation'])->name('campaigns.automation')->middleware('can:manage-campaigns');

    // Emails (read: view-emails/manage-emails, write: manage-emails)
    Route::view('emails/drafts', 'emails.drafts')->name('emails.drafts')->middleware('can:view-emails');
    Route::view('emails/pending', 'emails.pending')->name('emails.pending')->middleware('can:view-emails');
    Route::view('emails/scheduled', 'emails.scheduled')->name('emails.scheduled')->middleware('can:view-emails');
    Route::view('emails/sent', 'emails.sent')->name('emails.sent')->middleware('can:view-emails');
    Route::view('emails/failed', 'emails.failed')->name('emails.failed')->middleware('can:view-emails');
    Route::view('emails/queue', 'emails.queue')->name('emails.queue')->middleware('can:view-emails');
    Route::view('emails/history', 'emails.history')->name('emails.history')->middleware('can:view-emails');
    Route::view('emails/preview/{id}', 'emails.preview')->name('emails.preview')->middleware('can:view-emails');
    Route::post('emails/preview/{id}', [ApplicationController::class, 'emailPreviewAction'])->middleware('can:manage-emails');

    // AI Assistant (read: view-emails/manage-emails, write: manage-emails)
    Route::view('ai-assistant', 'ai.assistant')->name('ai-assistant')->middleware('can:view-emails');
    Route::post('ai-assistant', [ApplicationController::class, 'aiAssistant'])->middleware('can:manage-emails');

    // Templates (read: view-emails/manage-emails, write: manage-emails)
    Route::view('templates', 'templates.index')->name('templates.index')->middleware('can:view-emails');
    Route::view('templates/create', 'templates.create')->name('templates.create')->middleware('can:manage-emails');
    Route::view('templates/{id}/edit', 'templates.edit')->name('templates.edit')->middleware('can:manage-emails');
    Route::post('templates/create', [TemplateController::class, 'store'])->middleware('can:manage-emails');
    Route::post('templates/ai-generate', [TemplateController::class, 'aiGenerate'])->name('templates.ai-generate')->middleware('can:manage-emails');
    Route::put('templates/{id}/edit', [TemplateController::class, 'update'])->middleware('can:manage-emails');

    // Analytics & Reports (read: view-reports)
    Route::get('analytics', [ApplicationController::class, 'analytics'])->name('analytics')->middleware('can:view-reports');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index')->middleware('can:view-reports');
    Route::get('reports/campaign/{id}', [ReportController::class, 'campaign'])->name('reports.campaign')->middleware('can:view-reports');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export')->middleware('can:view-reports');

    // Settings (read+write: manage-settings only)
    Route::view('settings/company', 'settings.company')->name('settings.company')->middleware('can:manage-settings');
    Route::view('settings/smtp', 'settings.smtp')->name('settings.smtp')->middleware('can:manage-settings');
    Route::view('settings/ai', 'settings.ai')->name('settings.ai')->middleware('can:manage-settings');
    Route::view('settings/sending', 'settings.sending')->name('settings.sending')->middleware('can:manage-settings');
    Route::view('settings/users', 'settings.users')->name('settings.users')->middleware('can:manage-settings');
    Route::view('settings/system', 'settings.system')->name('settings.system')->middleware('can:manage-settings');
    Route::get('settings/audit', [AuditLogController::class, 'index'])->name('settings.audit')->middleware('can:manage-settings');

    Route::post('settings/company', [ApplicationController::class, 'saveCompany'])->middleware('can:manage-settings');
    Route::post('settings/company/upload-document', [CompanyDocumentController::class, 'upload'])->middleware('can:manage-settings')->name('settings.company.upload-document');
    Route::delete('settings/company/document/{id}', [CompanyDocumentController::class, 'delete'])->middleware('can:manage-settings')->name('settings.company.delete-document');
    Route::get('settings/company/document/{id}/download', [CompanyDocumentController::class, 'download'])->middleware('can:manage-settings')->name('settings.company.download-document');
    Route::post('settings/smtp', [ApplicationController::class, 'saveSmtp'])->middleware('can:manage-settings');
    Route::post('settings/ai', [ApplicationController::class, 'saveAiSettings'])->middleware('can:manage-settings');
    Route::post('settings/sending', [ApplicationController::class, 'saveSendingSettings'])->middleware('can:manage-settings');
    Route::post('settings/users', [ApplicationController::class, 'createUser'])->middleware('can:manage-settings');
    Route::post('settings/system', [ApplicationController::class, 'saveSystemSettings'])->middleware('can:manage-settings');
    Route::post('settings/system/export', [ApplicationController::class, 'export'])->middleware('can:manage-settings');

    // Inbox (read+write: manage-emails)
    Route::post('inbox/check', [ApplicationController::class, 'checkInbox'])->name('inbox.check')->middleware('can:manage-emails');
    Route::post('inbox/replies/{id}/read', [ApplicationController::class, 'markReplyRead'])->name('inbox.replies.read')->middleware('can:manage-emails');
    Route::post('inbox/replies/{id}/status', [ApplicationController::class, 'updateVendorFromReply'])->name('inbox.replies.status')->middleware('can:manage-emails');

    // Products (read: view-products/manage-products, write: manage-products)
    Route::get('products', [ProductController::class, 'index'])->name('products.index')->middleware('can:view-products');
    Route::get('products/{id}', [ProductController::class, 'show'])->name('products.show')->middleware('can:view-products');
    Route::post('vendors/{vendorId}/products', [ProductController::class, 'storeForVendor'])->name('products.create')->middleware('can:manage-products');
    Route::post('products', [ProductController::class, 'store'])->name('products.store')->middleware('can:manage-products');
    Route::put('products/{id}', [ProductController::class, 'update'])->name('products.update')->middleware('can:manage-products');
    Route::delete('products/{id}', [ProductController::class, 'destroy'])->name('products.destroy')->middleware('can:manage-products');
    Route::post('products/bulk', [ProductController::class, 'bulkAction'])->name('products.bulk')->middleware('can:manage-products');

    // Brand Approval (write: manage-vendors)
    Route::post('vendors/{vendorId}/brand-approval', [VendorBrandApprovalController::class, 'save'])->name('brand-approval.save')->middleware('can:manage-vendors');

    // Bulk Actions (write: manage-vendors)
    Route::post('vendors/bulk', [VendorController::class, 'bulkAction'])->name('vendors.bulk')->middleware('can:manage-vendors');
    Route::get('vendors/export/csv', [VendorController::class, 'export'])->name('vendors.export')->middleware('can:view-vendors');
    Route::get('vendors/{vendorId}/products/export', [ProductController::class, 'exportByVendor'])->name('products.export')->middleware('can:view-products');

    // Finance Module - Purchase Orders (read: view-finance/manage-finance, write: manage-finance)
    Route::get('finance/purchase-orders', [FinanceController::class, 'purchaseOrderIndex'])->name('finance.po.index')->middleware('can:view-finance');
    Route::get('finance/purchase-orders/create', [FinanceController::class, 'purchaseOrderCreate'])->name('finance.po.create')->middleware('can:manage-finance');
    Route::post('finance/purchase-orders', [FinanceController::class, 'purchaseOrderStore'])->name('finance.po.store')->middleware('can:manage-finance');
    Route::get('finance/purchase-orders/{id}/edit', [FinanceController::class, 'purchaseOrderEdit'])->name('finance.po.edit')->middleware('can:manage-finance');
    Route::put('finance/purchase-orders/{id}', [FinanceController::class, 'purchaseOrderUpdate'])->name('finance.po.update')->middleware('can:manage-finance');
    Route::get('finance/purchase-orders/{id}', [FinanceController::class, 'purchaseOrderShow'])->name('finance.po.show')->middleware('can:view-finance');
    Route::post('finance/purchase-orders/{id}/status', [FinanceController::class, 'purchaseOrderUpdateStatus'])->name('finance.po.status')->middleware('can:manage-finance');
    Route::post('finance/purchase-orders/{id}/payment', [FinanceController::class, 'purchaseOrderUpdatePayment'])->name('finance.po.payment')->middleware('can:manage-finance');
    Route::post('finance/purchase-orders/{id}/receive', [FinanceController::class, 'purchaseOrderReceiveItem'])->name('finance.po.receive')->middleware('can:manage-finance');
    Route::post('finance/purchase-orders/{id}/receive-all', [FinanceController::class, 'purchaseOrderReceiveAll'])->name('finance.po.receive-all')->middleware('can:manage-finance');

    // Finance Module - Amazon Sales (read: view-finance/manage-finance, write: manage-finance)
    Route::get('finance/dashboard', [FinanceController::class, 'dashboard'])->name('finance.dashboard')->middleware('can:view-finance');
    Route::get('finance/sales', [FinanceController::class, 'salesIndex'])->name('finance.sales.index')->middleware('can:view-finance');
    Route::get('finance/sales/create', [FinanceController::class, 'salesCreate'])->name('finance.sales.create')->middleware('can:manage-finance');
    Route::post('finance/sales', [FinanceController::class, 'salesStore'])->name('finance.sales.store')->middleware('can:manage-finance');
    Route::get('finance/sales/{id}', [FinanceController::class, 'salesShow'])->name('finance.sales.show')->middleware('can:view-finance');
    Route::get('finance/sales/{id}/edit', [FinanceController::class, 'salesEdit'])->name('finance.sales.edit')->middleware('can:manage-finance');
    Route::put('finance/sales/{id}', [FinanceController::class, 'salesUpdate'])->name('finance.sales.update')->middleware('can:manage-finance');
    Route::post('finance/sales/{id}/status', [FinanceController::class, 'salesUpdateStatus'])->name('finance.sales.status')->middleware('can:manage-finance');
    Route::get('finance/sales/import/csv', [FinanceController::class, 'salesImportCsv'])->name('finance.sales.import.csv')->middleware('can:manage-finance');
    Route::post('finance/sales/import/csv', [FinanceController::class, 'salesImportCsvStore'])->name('finance.sales.import.csv.store')->middleware('can:manage-finance');
    Route::get('finance/sales/batch', [FinanceController::class, 'salesBatchCreate'])->name('finance.sales.batch')->middleware('can:manage-finance');
    Route::post('finance/sales/batch', [FinanceController::class, 'salesBatchStore'])->name('finance.sales.batch.store')->middleware('can:manage-finance');

    // Finance Module - Expenses (read: view-finance/manage-finance, write: manage-finance)
    Route::get('finance/expenses', [FinanceController::class, 'expenseIndex'])->name('finance.expenses.index')->middleware('can:view-finance');
    Route::post('finance/expenses', [FinanceController::class, 'expenseStore'])->name('finance.expenses.store')->middleware('can:manage-finance');
    Route::post('finance/expenses/{id}/status', [FinanceController::class, 'expenseUpdateStatus'])->name('finance.expenses.status')->middleware('can:manage-finance');

    // Finance Module - Reports (read: view-finance/manage-finance)
    Route::get('finance/profit-loss', [FinanceController::class, 'profitLossReport'])->name('finance.pnl')->middleware('can:view-finance');
    Route::post('finance/refresh-pnl-cache', [FinanceController::class, 'refreshPnlCache'])->name('finance.pnl.refresh')->middleware('can:manage-finance');
    Route::get('finance/order-tracking', [FinanceController::class, 'orderTracking'])->name('finance.tracking')->middleware('can:view-finance');

    // Finance Module - Amazon Settlement Import (read: view-finance/manage-finance, write: manage-finance)
    Route::get('finance/settlements', [FinanceController::class, 'settlementIndex'])->name('finance.settlements.index')->middleware('can:view-finance');
    Route::get('finance/settlements/upload', [FinanceController::class, 'settlementUpload'])->name('finance.settlements.upload')->middleware('can:manage-finance');
    Route::post('finance/settlements', [FinanceController::class, 'settlementStore'])->name('finance.settlements.store')->middleware('can:manage-finance');
    Route::get('finance/settlements/{id}', [FinanceController::class, 'settlementShow'])->name('finance.settlements.show')->middleware('can:view-finance');
    Route::post('finance/settlements/{id}/commit', [FinanceController::class, 'settlementCommit'])->name('finance.settlements.commit')->middleware('can:manage-finance');
    Route::delete('finance/settlements/{id}', [FinanceController::class, 'settlementDestroy'])->name('finance.settlements.destroy')->middleware('can:manage-finance');
    Route::get('finance/reconciliation', [FinanceController::class, 'settlementReconciliation'])->name('finance.reconciliation')->middleware('can:view-finance');

    // Finance Module - AI Analysis (read: view-finance/manage-finance)
    Route::get('finance/ai-analysis', [FinanceController::class, 'aiAnalysis'])->name('finance.ai-analysis')->middleware('can:view-finance');
    Route::post('finance/ai-categorize', [FinanceController::class, 'aiCategorizeExpense'])->name('finance.ai-categorize')->middleware('can:manage-finance');

    // Finance Module - Tax Rates (read: view-finance/manage-finance, write: manage-finance)
    Route::get('finance/tax-rates', [FinanceController::class, 'taxRates'])->name('finance.tax.index')->middleware('can:view-finance');
    Route::post('finance/tax-rates/seed', [FinanceController::class, 'seedTaxRates'])->name('finance.tax.seed')->middleware('can:manage-finance');

    // Amazon SP-API Settings & Sync (manage-settings)
    Route::get('settings/amazon', [FinanceController::class, 'amazonSettings'])->name('settings.amazon')->middleware('can:manage-settings');
    Route::post('settings/amazon', [FinanceController::class, 'amazonSettingsSave'])->name('settings.amazon.save')->middleware('can:manage-settings');
    Route::post('settings/amazon/disconnect', [FinanceController::class, 'amazonDisconnect'])->name('settings.amazon.disconnect')->middleware('can:manage-settings');
    Route::post('settings/amazon/sync', [FinanceController::class, 'amazonSync'])->name('settings.amazon.sync')->middleware('can:manage-settings');

    // Notifications (all authenticated users)
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // Global Search (all authenticated users)
    Route::get('search', [SearchController::class, 'global'])->name('search');
});

require __DIR__.'/auth.php';

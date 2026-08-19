<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [ApplicationController::class, 'dashboard'])->name('dashboard');
    Route::view('profile', 'profile')->name('profile');

    // Vendors
    Route::get('vendors', [ApplicationController::class, 'vendorIndex'])->name('vendors.index');
    Route::view('vendors/create', 'vendors.create')->name('vendors.create');
    Route::view('vendors/import', 'vendors.import')->name('vendors.import');
    Route::get('vendors/{id}', [ApplicationController::class, 'showVendor'])->name('vendors.show');
    Route::view('vendors/{id}/edit', 'vendors.edit')->name('vendors.edit');
    Route::post('vendors/create', [ApplicationController::class, 'createVendor']);
    Route::post('vendors/import', [ApplicationController::class, 'importVendors']);
    Route::post('vendors/{id}', [ApplicationController::class, 'vendorAction']);
    Route::put('vendors/{id}/edit', [ApplicationController::class, 'updateVendor']);
    Route::post('vendors/{id}/status', [ApplicationController::class, 'updateVendorStatus'])->name('vendors.status');
    Route::post('vendors/{id}/priority', [ApplicationController::class, 'updateVendorPriority'])->name('vendors.priority');
    Route::post('vendors/{id}/generate-document-response', [ApplicationController::class, 'generateDocumentResponse'])->name('vendors.generate-document-response');
    Route::delete('vendors/{id}', [ApplicationController::class, 'deleteVendor'])->name('vendors.destroy');

    // Suppression List
    Route::view('suppression', 'suppression.index')->name('suppression.index');

    // Campaigns
    Route::view('campaigns', 'campaigns.index')->name('campaigns.index');
    Route::view('campaigns/create', 'campaigns.create')->name('campaigns.create');
    Route::view('campaigns/{id}', 'campaigns.show')->name('campaigns.show');
    Route::post('campaigns/create', [ApplicationController::class, 'createCampaign']);
    Route::post('campaigns/{id}/add-vendors', [ApplicationController::class, 'addVendorsToCampaign']);
    Route::post('campaigns/{id}/remove-vendor', [ApplicationController::class, 'removeVendorFromCampaign']);
    Route::post('campaigns/{id}/generate-emails', [ApplicationController::class, 'bulkGenerateEmails']);
    Route::post('campaigns/{id}/start', [ApplicationController::class, 'startCampaign']);
    Route::post('campaigns/{id}/pause', [ApplicationController::class, 'pauseCampaign']);
    Route::put('campaigns/{id}/update-automation', [ApplicationController::class, 'updateCampaignAutomation'])->name('campaigns.automation');

    // Emails
    Route::view('emails/drafts', 'emails.drafts')->name('emails.drafts');
    Route::view('emails/pending', 'emails.pending')->name('emails.pending');
    Route::view('emails/scheduled', 'emails.scheduled')->name('emails.scheduled');
    Route::view('emails/sent', 'emails.sent')->name('emails.sent');
    Route::view('emails/failed', 'emails.failed')->name('emails.failed');
    Route::view('emails/queue', 'emails.queue')->name('emails.queue');
    Route::view('emails/history', 'emails.history')->name('emails.history');
    Route::view('emails/preview/{id}', 'emails.preview')->name('emails.preview');
    Route::post('emails/preview/{id}', [ApplicationController::class, 'emailPreviewAction']);

    // AI Assistant
    Route::view('ai-assistant', 'ai.assistant')->name('ai-assistant');
    Route::post('ai-assistant', [ApplicationController::class, 'aiAssistant']);

    // Templates
    Route::view('templates', 'templates.index')->name('templates.index');
    Route::view('templates/create', 'templates.create')->name('templates.create');
    Route::view('templates/{id}/edit', 'templates.edit')->name('templates.edit');
    Route::post('templates/create', [ApplicationController::class, 'createTemplate']);
    Route::put('templates/{id}/edit', [ApplicationController::class, 'updateTemplate']);

    // Analytics
    Route::get('analytics', [ApplicationController::class, 'analytics'])->name('analytics');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/campaign/{id}', [ReportController::class, 'campaign'])->name('reports.campaign');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Settings
    Route::view('settings/company', 'settings.company')->name('settings.company')->middleware('can:manage-settings');
    Route::view('settings/smtp', 'settings.smtp')->name('settings.smtp')->middleware('can:manage-settings');
    Route::view('settings/ai', 'settings.ai')->name('settings.ai')->middleware('can:manage-settings');
    Route::view('settings/sending', 'settings.sending')->name('settings.sending')->middleware('can:manage-settings');
    Route::view('settings/users', 'settings.users')->name('settings.users')->middleware('can:manage-settings');
    Route::view('settings/system', 'settings.system')->name('settings.system')->middleware('can:manage-settings');
    Route::view('settings/audit', 'settings.audit')->name('settings.audit')->middleware('can:manage-settings');

    Route::post('settings/company', [ApplicationController::class, 'saveCompany'])->middleware('can:manage-settings');
    Route::post('settings/company/upload-document', [ApplicationController::class, 'uploadCompanyDocument'])->middleware('can:manage-settings')->name('settings.company.upload-document');
    Route::delete('settings/company/document/{id}', [ApplicationController::class, 'deleteCompanyDocument'])->middleware('can:manage-settings')->name('settings.company.delete-document');
    Route::post('settings/smtp', [ApplicationController::class, 'saveSmtp'])->middleware('can:manage-settings');
    Route::post('settings/ai', [ApplicationController::class, 'saveAiSettings'])->middleware('can:manage-settings');
    Route::post('settings/sending', [ApplicationController::class, 'saveSendingSettings'])->middleware('can:manage-settings');
    Route::post('settings/users', [ApplicationController::class, 'createUser'])->middleware('can:manage-settings');
    Route::post('settings/system', [ApplicationController::class, 'saveSystemSettings'])->middleware('can:manage-settings');
    Route::post('settings/system/export', [ApplicationController::class, 'export'])->middleware('can:manage-settings');

    // Inbox
    Route::post('inbox/check', [ApplicationController::class, 'checkInbox'])->name('inbox.check');
    Route::post('inbox/replies/{id}/read', [ApplicationController::class, 'markReplyRead'])->name('inbox.replies.read');
    Route::post('inbox/replies/{id}/status', [ApplicationController::class, 'updateVendorFromReply'])->name('inbox.replies.status');

    // Products
    Route::get('products', [ApplicationController::class, 'productIndex'])->name('products.index');
    Route::get('products/{id}', [ApplicationController::class, 'showProduct'])->name('products.show');
    Route::post('vendors/{vendorId}/products', [ApplicationController::class, 'createProduct'])->name('products.create');
    Route::put('products/{id}', [ApplicationController::class, 'updateProduct'])->name('products.update');
    Route::delete('products/{id}', [ApplicationController::class, 'deleteProduct'])->name('products.destroy');
    Route::post('products/bulk', [ApplicationController::class, 'bulkProductAction'])->name('products.bulk');

    // Brand Approval
    Route::post('vendors/{vendorId}/brand-approval', [ApplicationController::class, 'saveBrandApproval'])->name('brand-approval.save');

    // Bulk Actions
    Route::post('vendors/bulk', [ApplicationController::class, 'bulkVendorAction'])->name('vendors.bulk');
    Route::get('vendors/export/csv', [ApplicationController::class, 'exportVendors'])->name('vendors.export');
    Route::get('vendors/{vendorId}/products/export', [ApplicationController::class, 'exportProducts'])->name('products.export');

    // Finance Module - Purchase Orders
    Route::get('finance/purchase-orders', [FinanceController::class, 'purchaseOrderIndex'])->name('finance.po.index');
    Route::get('finance/purchase-orders/create', [FinanceController::class, 'purchaseOrderCreate'])->name('finance.po.create');
    Route::post('finance/purchase-orders', [FinanceController::class, 'purchaseOrderStore'])->name('finance.po.store');
    Route::get('finance/purchase-orders/{id}', [FinanceController::class, 'purchaseOrderShow'])->name('finance.po.show');
    Route::post('finance/purchase-orders/{id}/status', [FinanceController::class, 'purchaseOrderUpdateStatus'])->name('finance.po.status');
    Route::post('finance/purchase-orders/{id}/payment', [FinanceController::class, 'purchaseOrderUpdatePayment'])->name('finance.po.payment');
    Route::post('finance/purchase-orders/{id}/receive', [FinanceController::class, 'purchaseOrderReceiveItem'])->name('finance.po.receive');
    Route::post('finance/purchase-orders/{id}/receive-all', [FinanceController::class, 'purchaseOrderReceiveAll'])->name('finance.po.receive-all');

    // Finance Module - Amazon Sales
    Route::get('finance/dashboard', [FinanceController::class, 'dashboard'])->name('finance.dashboard');
    Route::get('finance/sales', [FinanceController::class, 'salesIndex'])->name('finance.sales.index');
    Route::get('finance/sales/create', [FinanceController::class, 'salesCreate'])->name('finance.sales.create');
    Route::post('finance/sales', [FinanceController::class, 'salesStore'])->name('finance.sales.store');
    Route::get('finance/sales/{id}', [FinanceController::class, 'salesShow'])->name('finance.sales.show');
    Route::get('finance/sales/{id}/edit', [FinanceController::class, 'salesEdit'])->name('finance.sales.edit');
    Route::put('finance/sales/{id}', [FinanceController::class, 'salesUpdate'])->name('finance.sales.update');
    Route::post('finance/sales/{id}/status', [FinanceController::class, 'salesUpdateStatus'])->name('finance.sales.status');
    Route::get('finance/sales/import/csv', [FinanceController::class, 'salesImportCsv'])->name('finance.sales.import.csv');
    Route::post('finance/sales/import/csv', [FinanceController::class, 'salesImportCsvStore'])->name('finance.sales.import.csv.store');
    Route::get('finance/sales/batch', [FinanceController::class, 'salesBatchCreate'])->name('finance.sales.batch');
    Route::post('finance/sales/batch', [FinanceController::class, 'salesBatchStore'])->name('finance.sales.batch.store');

    // Finance Module - Expenses
    Route::get('finance/expenses', [FinanceController::class, 'expenseIndex'])->name('finance.expenses.index');
    Route::post('finance/expenses', [FinanceController::class, 'expenseStore'])->name('finance.expenses.store');
    Route::post('finance/expenses/{id}/status', [FinanceController::class, 'expenseUpdateStatus'])->name('finance.expenses.status');

    // Finance Module - Profit & Loss
    Route::get('finance/profit-loss', [FinanceController::class, 'profitLossReport'])->name('finance.pnl');

    // Finance Module - Order Tracking
    Route::get('finance/order-tracking', [FinanceController::class, 'orderTracking'])->name('finance.tracking');

    // Finance Module - Tax Rates
    Route::get('finance/tax-rates', [FinanceController::class, 'taxRates'])->name('finance.tax.index');
    Route::post('finance/tax-rates/seed', [FinanceController::class, 'seedTaxRates'])->name('finance.tax.seed');

    // Amazon SP-API Settings & Sync
    Route::get('settings/amazon', [FinanceController::class, 'amazonSettings'])->name('settings.amazon')->middleware('can:manage-settings');
    Route::post('settings/amazon', [FinanceController::class, 'amazonSettingsSave'])->name('settings.amazon.save')->middleware('can:manage-settings');
    Route::post('settings/amazon/disconnect', [FinanceController::class, 'amazonDisconnect'])->name('settings.amazon.disconnect')->middleware('can:manage-settings');
    Route::post('settings/amazon/sync', [FinanceController::class, 'amazonSync'])->name('settings.amazon.sync')->middleware('can:manage-settings');

    // Notifications
    Route::post('notifications/{id}/read', [ApplicationController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('notifications/read-all', [ApplicationController::class, 'markAllNotificationsRead'])->name('notifications.read-all');

    // Global Search
    Route::get('search', [ApplicationController::class, 'globalSearch'])->name('search');
});

require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CostingController;
use App\Http\Controllers\CostingExcelTemplateController;
use App\Http\Controllers\CostingGroupController;
use App\Http\Controllers\CostingApprovalController;
use App\Http\Controllers\Database\DocumentRecapController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\DocumentReceiptController;
use App\Http\Controllers\DocumentControlRegistrationController;
use App\Http\Controllers\DocumentControlInboxController;
use App\Http\Controllers\BreakdownInboxController;
use App\Http\Controllers\BusinessCategoryContextController;
use App\Http\Controllers\ProjectA00Controller;
use App\Http\Controllers\ProjectGroupController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrackingDocumentController;
use App\Http\Controllers\NewPartRequestInboxController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TubesController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// All app routes require authentication
Route::middleware('auth')->group(function () {
    Route::post('/business-category-context',[BusinessCategoryContextController::class,'update'])->name('business-category-context.update');
    Route::post('/notifications/{notification}/open',[NotificationController::class,'open'])->name('notifications.open');
    Route::post('/notifications/read-all',[NotificationController::class,'markAllRead'])->name('notifications.read-all');
    Route::patch('/costing-group-items/{item}/pics',[CostingGroupController::class,'updateItemPics'])->name('control-project.costing-group-items.pics');
    Route::middleware('permission:control_project')->prefix('control-project')->name('control-project.')->group(function(){
        Route::get('/a00',[ProjectA00Controller::class,'index'])->name('a00.index');
        Route::get('/a00/create',[ProjectA00Controller::class,'create'])->name('a00.create');
        Route::post('/a00',[ProjectA00Controller::class,'store'])->name('a00.store');
        Route::get('/a00/{a00}',[ProjectA00Controller::class,'show'])->name('a00.show');
        Route::get('/a00/{a00}/pdf',[ProjectA00Controller::class,'downloadPdf'])->name('a00.pdf');
        Route::get('/a00/{a00}/excel',[ProjectA00Controller::class,'downloadExcel'])->name('a00.excel');
        Route::get('/a00/{a00}/edit',[ProjectA00Controller::class,'edit'])->name('a00.edit');
        Route::put('/a00/{a00}',[ProjectA00Controller::class,'update'])->name('a00.update');
        Route::delete('/a00/{a00}',[ProjectA00Controller::class,'destroy'])->name('a00.destroy');
        Route::get('/a00/{a00}/edit-operational',[ProjectA00Controller::class,'editOperational'])->name('a00.edit-operational');
        Route::put('/a00/{a00}/operational',[ProjectA00Controller::class,'updateOperational'])->name('a00.update-operational');
        Route::post('/costing-groups/{group}/items',[CostingGroupController::class,'addItem'])->name('costing-groups.items.add');
        Route::delete('/costing-group-items/{item}',[CostingGroupController::class,'removeItem'])->name('costing-group-items.remove');
    });
    Route::middleware('permission:document_control')->prefix('document-control')->name('document-control.')->group(function () {
        Route::get('/inbox', [DocumentControlInboxController::class, 'index'])->name('inbox');
        Route::get('/registrations', [DocumentControlRegistrationController::class, 'index'])->name('index');
        Route::post('/registrations', [DocumentControlRegistrationController::class, 'store'])->name('store');
        Route::put('/registrations/{registration}', [DocumentControlRegistrationController::class, 'update'])->name('update');
        Route::patch('/registrations/{registration}/cell', [DocumentControlRegistrationController::class, 'updateCell'])->name('update-cell');
        Route::post('/rows', [DocumentControlRegistrationController::class, 'insertRow'])->name('rows.insert');
        Route::patch('/registrations/{registration}/custom-cell', [DocumentControlRegistrationController::class, 'updateCustomCell'])->name('custom-cell.update');
        Route::post('/columns', [DocumentControlRegistrationController::class, 'storeColumn'])->name('columns.store');
        Route::patch('/columns/{column}', [DocumentControlRegistrationController::class, 'updateColumn'])->name('columns.update');
        Route::delete('/columns/{column}', [DocumentControlRegistrationController::class, 'destroyColumn'])->name('columns.destroy');
        Route::delete('/registrations/{registration}', [DocumentControlRegistrationController::class, 'destroy'])->name('destroy');
        Route::post('/registrations/import', [DocumentControlRegistrationController::class, 'import'])->name('import');
        Route::post('/tasks/{task}/complete', [DocumentControlRegistrationController::class, 'completeDistribution'])->name('tasks.complete');
        Route::post('/revisions/{revision}/skip-drawing', [DocumentControlRegistrationController::class, 'skipDrawing'])->name('drawing.skip');
    });
    Route::middleware('permission:inbox_breakdown')->prefix('breakdown')->name('breakdown.')->group(function () {
        Route::get('/inbox', [BreakdownInboxController::class, 'index'])->name('inbox');
        Route::post('/manual', [BreakdownInboxController::class, 'storeManual'])->name('manual.store');
        Route::post('/tasks/{task}/complete', [BreakdownInboxController::class, 'complete'])->name('tasks.complete');
        Route::post('/tasks/{task}/start-costing', [BreakdownInboxController::class, 'startCosting'])->name('tasks.start-costing');
        Route::post('/tasks/{task}/revision', [BreakdownInboxController::class, 'uploadRevision'])->name('tasks.revision');
        Route::delete('/tasks/{task}', [BreakdownInboxController::class, 'destroyTask'])->name('tasks.destroy');
    });
    Route::get('/project-selection', [AuthController::class, 'projectSelection'])->name('project-selection');
    Route::get('/costing-product-performance', [AuthController::class, 'productPerformance'])->name('costing-product-performance');

    // Project grouped page
    // Parent = Business Category + Customer + Model
    // Child = Part Number / Part Name / Revision
    Route::middleware('permission:project')->group(function () {
        Route::get('/project', [ProjectGroupController::class, 'index'])->name('project');
        Route::delete('/project/group', [ProjectGroupController::class, 'destroyGroup'])->name('project.group.destroy');
        Route::get('/tracking-documents', [ProjectGroupController::class, 'index'])->name('tracking-documents.index');
    });
    Route::middleware('permission:inbox_costing')->group(function () {
        Route::get('/costing/inbox', [ProjectGroupController::class, 'costingInbox'])->name('costing.inbox');
        Route::get('/costing/documents/{revision}/download-cogm', [CostingController::class, 'downloadExportedCogm'])->name('costing.cogm.download');
        Route::post('/costing/revisions/{revision}', [ProjectGroupController::class, 'uploadCostingRevision'])->name('costing.revisions.store');
        Route::post('/costing/revisions/{revision}/manual-cogm', [ProjectGroupController::class, 'uploadManualCogm'])->name('costing.manual-cogm.store');
        Route::get('/costing-groups/{group}',[CostingGroupController::class,'workspace'])->name('costing-groups.workspace');
        Route::post('/costing-groups/{group}/draft',[CostingGroupController::class,'draft'])->name('control-project.costing-groups.draft');
        Route::post('/costing-groups/{group}/submit-approval',[CostingGroupController::class,'submitApproval'])->name('control-project.costing-groups.submit-approval');
        Route::post('/costing-groups/{group}/approve',[CostingGroupController::class,'approve'])->name('control-project.costing-groups.approve');
        Route::post('/costing-groups/{group}/final-file',[CostingGroupController::class,'uploadFinal'])->name('control-project.costing-groups.final-file');
        Route::post('/costing-groups/{group}/submit-final',[CostingGroupController::class,'submitFinal'])->name('control-project.costing-groups.submit-final');
    });
    Route::middleware('permission:inbox_new_part_request')->group(function () {
        Route::get('/new-part-request/inbox', [NewPartRequestInboxController::class, 'index'])->name('new-part-request.inbox');
        Route::post('/new-part-request/{revision}/submit', [NewPartRequestInboxController::class, 'submit'])->name('new-part-request.submit');
    });

    Route::post('/costing-approvals/{revision}/submit', [CostingApprovalController::class, 'submit'])->name('costing-approvals.submit');
    Route::post('/costing-approvals/{revision}/approve', [CostingApprovalController::class, 'approve'])->name('costing-approvals.approve');
    Route::post('/costing-approvals/{revision}/reject', [CostingApprovalController::class, 'reject'])->name('costing-approvals.reject');
    Route::post('/costing-approvals/{revision}/send-marketing', [CostingApprovalController::class, 'sendToMarketing'])->name('costing-approvals.send-marketing');
    Route::middleware('permission:inbox_marketing')->group(function () {
        Route::get('/marketing/cogm-inbox', [CostingApprovalController::class, 'marketingInbox'])->name('marketing.cogm-inbox');
        Route::get('/marketing/cogm-inbox/{submission}/costing', [CostingController::class, 'marketingCostingView'])->name('marketing.cogm-costing.show');
        Route::get('/marketing/cogm-inbox/{submission}/download', [CostingController::class, 'downloadImportedCogm'])->name('marketing.cogm-import.download');
        Route::get('/marketing/costing-documents/{revision}/download', [CostingController::class, 'downloadCostingEdit'])->name('marketing.costing-edit.download');
        Route::get('/marketing/cogm-inbox/{submission}/latest-update/download', [CostingApprovalController::class, 'downloadLatestUpdate'])->name('marketing.cogm-update.download');
        Route::post('/marketing/cogm-inbox/{submission}/comments', [CostingApprovalController::class, 'storeMarketingComment'])->name('marketing.cogm-comments.store');
        Route::post('/marketing/cogm-inbox/{submission}/status', [CostingApprovalController::class, 'updateMarketingStatus'])->name('marketing.cogm-status.update');
        Route::get('/marketing/bulky-cogm/{version}/download',[CostingGroupController::class,'download'])->name('marketing.bulky-cogm.download');
    });

    Route::get('/profile', function () {
        return view('profile.show');
    })->name('profile.show');

    // ── DASHBOARD ─────────────────────────────────────────────────────────────
    // Dashboard is the authenticated landing page for every role. Its contents
    // are scoped by CostingController according to the logged-in user's role/PIC.
    Route::get('/', [CostingController::class, 'dashboard'])->name('dashboard');
    Route::middleware('permission:dashboard')->group(function () {
        Route::get('/compare-costing', [CostingController::class, 'compare'])->name('compare.costing');
        Route::get('/compare-costing/revisions-search', [CostingController::class, 'searchCompareRevisions'])->name('compare.costing.revisions-search');
    });

    // ── LAPORAN ───────────────────────────────────────────────────────────────
    Route::middleware('permission:laporan')->group(function () {
        Route::get('/resume-cogm', [ReportController::class, 'resumeCogm'])->name('resume-cogm');
        Route::get('/analisis-tren', [ReportController::class, 'analisisTren'])->name('analisis-tren');
        Route::get('/analisis-tren/canceled-failed', [ReportController::class, 'analisisTrenCanceled'])->name('analisis-tren.canceled');
        Route::get('/analisis-tren/detail-dokumen-engineering', [ReportController::class, 'analisisTrenEngineering'])->name('analisis-tren.engineering');
        Route::get('/laporan', [ReportController::class, 'laporan'])->name('laporan');
        Route::get('/laporan/export', [ReportController::class, 'exportLaporan'])->name('laporan.export');
    });

    // ── DATABASE ──────────────────────────────────────────────────────────────
    Route::middleware('permission:database')->group(function () {
        // Rate & Kurs
        Route::get('/database/rate-kurs', [ReportController::class, 'rateKurs'])->name('rate-kurs');
        Route::post('/database/rate-kurs', [ReportController::class, 'storeExchangeRate'])->name('rate-kurs.store');
        Route::put('/database/rate-kurs/{id}', [ReportController::class, 'updateExchangeRate'])->name('rate-kurs.update');
        Route::delete('/database/rate-kurs/{id}', [ReportController::class, 'destroyExchangeRate'])->name('rate-kurs.destroy');

        // Unpriced

        // Database index & products list
        Route::get('/database', [DatabaseController::class, 'index'])->name('database');
        Route::get('/database/products', [DatabaseController::class, 'products'])->name('database.products');

        // Parts
        Route::get('/database/parts', [DatabaseController::class, 'parts'])->name('database.parts');
        Route::get('/database/parts/template', [DatabaseController::class, 'downloadPartsTemplate'])->name('database.parts.template');
        Route::post('/database/parts/import', [DatabaseController::class, 'importPartsExcel'])->name('database.parts.import');
        Route::delete('/database/parts/bulk-delete', [DatabaseController::class, 'destroyPartsBulk'])->name('database.parts.destroy-bulk');
        Route::delete('/database/parts/destroy-all', [DatabaseController::class, 'destroyPartsAll'])->name('database.parts.destroy-all');
        Route::get('/database/parts/create', [DatabaseController::class, 'createPart'])->name('database.parts.create');
        Route::post('/database/parts', [DatabaseController::class, 'storePart'])->name('database.parts.store');
        Route::get('/database/parts/{id}/edit', [DatabaseController::class, 'editPart'])->name('database.parts.edit');
        Route::put('/database/parts/{id}', [DatabaseController::class, 'updatePart'])->name('database.parts.update');
        Route::delete('/database/parts/{id}', [DatabaseController::class, 'destroyPart'])->name('database.parts.destroy');

        // Wires
        Route::get('/database/wires', [DatabaseController::class, 'wires'])->name('database.wires');
        Route::get('/database/wires/template', [DatabaseController::class, 'downloadWiresTemplate'])->name('database.wires.template');
        Route::post('/database/wires/import', [DatabaseController::class, 'importWiresExcel'])->name('database.wires.import');
        Route::post('/database/wires/switch-rate-month', [DatabaseController::class, 'switchWireRateMonth'])->name('database.wires.switch-rate-month');
        Route::post('/database/wires/rates', [DatabaseController::class, 'storeWireRate'])->name('database.wires.rates.store');
        Route::put('/database/wires/rates/{id}', [DatabaseController::class, 'updateWireRate'])->name('database.wires.rates.update');
        Route::delete('/database/wires/rates/{id}', [DatabaseController::class, 'destroyWireRate'])->name('database.wires.rates.destroy');
        Route::post('/database/wires', [DatabaseController::class, 'storeWire'])->name('database.wires.store');
        Route::put('/database/wires/{id}', [DatabaseController::class, 'updateWire'])->name('database.wires.update');
        Route::delete('/database/wires/{id}', [DatabaseController::class, 'destroyWire'])->name('database.wires.destroy');

        // Tubes
        Route::get('/database/tubes', [TubesController::class, 'index'])->name('database.tubes');
        Route::post('/database/tubes', [TubesController::class, 'store'])->name('database.tubes.store');
        Route::put('/database/tubes/{tube}', [TubesController::class, 'update'])->name('database.tubes.update');
        Route::delete('/database/tubes/{tube}', [TubesController::class, 'destroy'])->name('database.tubes.destroy');

        // Customers
        Route::get('/database/customers', [DatabaseController::class, 'customers'])->name('database.customers');
        Route::post('/database/customers', [DatabaseController::class, 'storeCustomer'])->name('database.customers.store');
        Route::put('/database/customers/{id}', [DatabaseController::class, 'updateCustomer'])->name('database.customers.update');
        Route::delete('/database/customers/{id}', [DatabaseController::class, 'destroyCustomer'])->name('database.customers.destroy');

        // Cycle Time Templates
        Route::get('/database/cycle-time-templates', [DatabaseController::class, 'cycleTimeTemplates'])->name('database.cycle-time-templates');
        Route::post('/database/cycle-time-templates', [DatabaseController::class, 'storeCycleTimeTemplate'])->name('database.cycle-time-templates.store');
        Route::put('/database/cycle-time-templates/{id}', [DatabaseController::class, 'updateCycleTimeTemplate'])->name('database.cycle-time-templates.update');
        Route::delete('/database/cycle-time-templates/{id}', [DatabaseController::class, 'destroyCycleTimeTemplate'])->name('database.cycle-time-templates.destroy');

        // Business Categories
        Route::get('/database/business-categories', [DatabaseController::class, 'businessCategories'])->name('database.business-categories');
        Route::post('/database/business-categories', [DatabaseController::class, 'storeBusinessCategory'])->name('database.business-categories.store');
        Route::put('/database/business-categories/{id}', [DatabaseController::class, 'updateBusinessCategory'])->name('database.business-categories.update');
        Route::delete('/database/business-categories/{id}', [DatabaseController::class, 'destroyBusinessCategory'])->name('database.business-categories.destroy');

        // Plants
        Route::get('/database/plants', [DatabaseController::class, 'plants'])->name('database.plants');
        Route::post('/database/plants', [DatabaseController::class, 'storePlant'])->name('database.plants.store');
        Route::put('/database/plants/{id}', [DatabaseController::class, 'updatePlant'])->name('database.plants.update');
        Route::delete('/database/plants/{id}', [DatabaseController::class, 'destroyPlant'])->name('database.plants.destroy');

        // PICs
        Route::get('/database/pics', [DatabaseController::class, 'pics'])->name('database.pics');
        Route::post('/database/pics', [DatabaseController::class, 'storePic'])->name('database.pics.store');
        Route::put('/database/pics/{id}', [DatabaseController::class, 'updatePic'])->name('database.pics.update');
        Route::delete('/database/pics/{id}', [DatabaseController::class, 'destroyPic'])->name('database.pics.destroy');

        // Project Documents
        Route::get('/database/project-documents', [DatabaseController::class, 'projectDocuments'])->name('database.project-documents');
        Route::get('/database/document-recap', [DocumentRecapController::class, 'index'])->name('database.document-recap');
        Route::put('/database/project-documents/{id}', [DatabaseController::class, 'updateProjectDocument'])->name('database.project-documents.update');
        Route::delete('/database/project-documents/{id}', [DatabaseController::class, 'destroyProjectDocument'])->name('database.project-documents.destroy');
        Route::get('/database/costing-excel-templates', [CostingExcelTemplateController::class, 'index'])->name('database.costing-excel-templates.index');
        Route::post('/database/costing-excel-templates', [CostingExcelTemplateController::class, 'store'])->name('database.costing-excel-templates.store');
        Route::get('/database/costing-excel-templates/{template}/download', [CostingExcelTemplateController::class, 'download'])->name('database.costing-excel-templates.download');
        Route::delete('/database/costing-excel-templates/{template}', [CostingExcelTemplateController::class, 'destroy'])->name('database.costing-excel-templates.destroy');
    });

    // ── INPUT DATA ────────────────────────────────────────────────────────────
    Route::middleware('permission:input_data')->group(function () {
        // Form Costing
        Route::get('/form', [CostingController::class, 'form'])->name('form');
        Route::post('/costing/store', [CostingController::class, 'store'])->name('costing.store');
        Route::post('/costing/material-quick-update', [CostingController::class, 'quickUpdateMaterial'])->name('costing.material-quick-update');
        Route::post('/costing/material-recalculate', [CostingController::class, 'recalculateMaterial'])->name('costing.material-recalculate');
        Route::post('/costing/selected-exchange-rate', [CostingController::class, 'rememberSelectedExchangeRate'])->name('costing.selected-exchange-rate');
        Route::post('/costing/material-excel/export', [CostingController::class, 'exportMaterialEditor'])->name('costing.material-excel.export');
        Route::post('/costing/material-excel/import', [CostingController::class, 'importMaterialEditor'])->name('costing.material-excel.import');
        Route::get('/costing/store', function () {
            return redirect(route('form', [], false))
                ->with('warning', 'Halaman simpan tidak bisa dibuka langsung. Silakan simpan data dari Form Costing.');
        })->name('costing.store.get');
        Route::get('/costing/import-partlist', fn () => redirect()->route('form'))->name('costing.import-partlist.get');
        Route::post('/costing/import-partlist', [CostingController::class, 'importPartlist'])->name('costing.import-partlist');
        Route::get('/costing/import-cogm', fn () => redirect()->route('form'))->name('costing.import-cogm.get');
        Route::post('/costing/import-cogm', [CostingController::class, 'importCogm'])->name('costing.import-cogm');
        Route::get('/costing/import-umh', fn () => redirect()->route('form'))->name('costing.import-umh.get');
        Route::post('/costing/import-umh', [CostingController::class, 'importUmh'])->name('costing.import-umh');
        Route::match(['post', 'patch'], '/costing/status-project/{revisionId}', [CostingController::class, 'updateStatusProject'])->name('costing.status-project.update');

        // Document Receipts
        Route::get('/document-receipts', [DocumentReceiptController::class, 'index'])->name('document-receipts.index');
        Route::post('/document-receipts', [DocumentReceiptController::class, 'store'])->name('document-receipts.store');
        Route::get('/document-receipts/{documentReceipt}/{type}', [DocumentReceiptController::class, 'download'])
            ->where('type', 'partlist|umh')
            ->name('document-receipts.download');

        // Tracking Documents (Project)
        Route::get('/tracking-documents/new', [TrackingDocumentController::class, 'create'])->name('tracking-documents.create');
        Route::post('/tracking-documents/receipt', [TrackingDocumentController::class, 'storeReceipt'])->name('tracking-documents.store-receipt');
        Route::post('/tracking-documents/{revision}/add-version', [TrackingDocumentController::class, 'addVersion'])->name('tracking-documents.add-version');
        Route::delete('/tracking-documents/{revision}/delete-version', [TrackingDocumentController::class, 'deleteVersion'])->name('tracking-documents.delete-version');
        Route::post('/tracking-documents/{revision}/process-form-input', [TrackingDocumentController::class, 'processToFormInput'])->name('tracking-documents.process-form-input');
        Route::post('/tracking-documents/{revision}/update-files', [TrackingDocumentController::class, 'updateFiles'])->name('tracking-documents.update-files');
        Route::post('/tracking-documents/{project}/update-project-info', [TrackingDocumentController::class, 'updateProjectInfo'])->name('tracking-documents.update-project-info');
        Route::delete('/tracking-documents/{project}', [TrackingDocumentController::class, 'destroyProject'])->name('tracking-documents.destroy-project');
        Route::post('/tracking-documents/{revision}/unpriced-price', [TrackingDocumentController::class, 'updateUnpricedPartPrice'])->name('tracking-documents.update-unpriced-price');
        Route::post('/tracking-documents/{revision}/unpriced-delete', [TrackingDocumentController::class, 'deleteUnpricedPart'])->name('tracking-documents.delete-unpriced-part');
        Route::post('/tracking-documents/{revision}/unpriced-bulk-delete', [TrackingDocumentController::class, 'bulkDeleteUnpricedParts'])->name('tracking-documents.bulk-delete-unpriced-parts');
        Route::get('/tracking-documents/{revision}/{type}', [TrackingDocumentController::class, 'download'])
            ->where('type', 'partlist|umh|a00|a04|a05')
            ->name('tracking-documents.download');
         Route::get('/tracking-documents/{revision}/export-unpriced/{format}', [TrackingDocumentController::class, 'exportUnpricedParts'])
             ->where('format', 'excel|pdf')
             ->name('tracking-documents.export-unpriced');
         Route::get('/tracking-documents/{revision}/export-new-part-request', [TrackingDocumentController::class, 'exportNewPartRequest'])
             ->name('tracking-documents.export-new-part-request');
         Route::post('/tracking-documents/{revision}/sync-new-part-request', [TrackingDocumentController::class, 'syncNewPartRequestRows'])
             ->name('tracking-documents.sync-new-part-request');
         Route::post('/tracking-documents/{revision}/import-new-part-request', [TrackingDocumentController::class, 'importNewPartRequest'])
             ->name('tracking-documents.import-new-part-request');
    });

    // ── USER MANAGEMENT (admin only) ──────────────────────────────────────────
    Route::middleware('permission:user_management')->group(function () {
        Route::get('/permissions', [AuthController::class, 'permissions'])->name('permissions');
        Route::post('/permissions', [AuthController::class, 'storeUser'])->name('permissions.store');
        Route::post('/permissions/update-access', [AuthController::class, 'updatePermission'])->name('permissions.update-access');
        Route::put('/permissions/{id}', [AuthController::class, 'updateUser'])->name('permissions.update');
        Route::delete('/permissions/{id}', [AuthController::class, 'destroyUser'])->name('permissions.destroy');

    });
});

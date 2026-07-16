<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BulkProjectActionController;
use App\Http\Controllers\CostingApprovalController;
use App\Http\Controllers\CostingAssistantController;
use App\Http\Controllers\CostingController;
use App\Http\Controllers\CostingDraftController;
use App\Http\Controllers\Database\DocumentRecapController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\DocumentReceiptController;
use App\Http\Controllers\ExportCenterController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\HelpCenterController;
use App\Http\Controllers\ImportSafetyController;
use App\Http\Controllers\MyTaskController;
use App\Http\Controllers\NotificationCenterController;
use App\Http\Controllers\OperationsCenterController;
use App\Http\Controllers\ProjectCollaborationController;
use App\Http\Controllers\ProjectGroupController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SlaPerformanceController;
use App\Http\Controllers\SystemCenterController;
use App\Http\Controllers\TrackingDocumentController;
use App\Http\Controllers\TubesController;
use App\Http\Controllers\UatFeedbackController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// All app routes require authentication
Route::middleware('auth')->group(function () {
    Route::get('/project-selection', [AuthController::class, 'projectSelection'])->name('project-selection');
    Route::get('/costing-product-performance', [AuthController::class, 'productPerformance'])->name('costing-product-performance');

    // Project grouped page
    // Parent = Business Category + Customer + Model
    // Child = Part Number / Part Name / Revision
    Route::get('/project', [ProjectGroupController::class, 'index'])->name('project');
    Route::get('/my-tasks', [MyTaskController::class, 'index'])->name('my-tasks');
    Route::get('/global-search', GlobalSearchController::class)->name('global-search');
    Route::get('/help-center', HelpCenterController::class)->name('help-center');
    Route::get('/export-center', [ExportCenterController::class, 'index'])->name('exports.index');
    Route::post('/export-center', [ExportCenterController::class, 'store'])->name('exports.store');
    Route::get('/export-center/{job}/download', [ExportCenterController::class, 'download'])->name('exports.download');
    Route::delete('/export-center/{job}', [ExportCenterController::class, 'destroy'])->name('exports.destroy');
    Route::get('/projects/revisions/{revision}/collaboration', [ProjectCollaborationController::class, 'show'])->name('project-collaboration.show');
    Route::patch('/projects/revisions/{revision}/deadline', [ProjectCollaborationController::class, 'updateDeadline'])->name('project-collaboration.deadline');
    Route::post('/projects/revisions/{revision}/comments', [ProjectCollaborationController::class, 'storeComment'])->name('project-collaboration.comments.store');
    Route::delete('/projects/revisions/{revision}/comments/{comment}', [ProjectCollaborationController::class, 'destroyComment'])->name('project-collaboration.comments.destroy');
    Route::get('/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read', [NotificationCenterController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationCenterController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/dismiss', [NotificationCenterController::class, 'dismiss'])->name('notifications.dismiss');
    Route::put('/notifications/preferences', [NotificationCenterController::class, 'updatePreferences'])->name('notifications.preferences');
    Route::post('/projects/bulk-action', BulkProjectActionController::class)->name('project.bulk-action');
    Route::post('/uat-feedback', [UatFeedbackController::class, 'store'])->name('uat-feedback.store');
    Route::get('/uat-feedback', [UatFeedbackController::class, 'index'])->name('uat-feedback.index');
    Route::patch('/uat-feedback/{feedback}', [UatFeedbackController::class, 'update'])->name('uat-feedback.update');
    Route::get('/tracking-documents', [ProjectGroupController::class, 'index'])->name('tracking-documents.index');

    Route::post('/costing-approvals/{revision}/submit', [CostingApprovalController::class, 'submit'])->name('costing-approvals.submit');
    Route::post('/costing-approvals/{revision}/approve', [CostingApprovalController::class, 'approve'])->name('costing-approvals.approve');
    Route::post('/costing-approvals/{revision}/reject', [CostingApprovalController::class, 'reject'])->name('costing-approvals.reject');
    Route::post('/costing-approvals/{revision}/send-marketing', [CostingApprovalController::class, 'sendToMarketing'])->name('costing-approvals.send-marketing');
    Route::get('/marketing/cogm-inbox', [CostingApprovalController::class, 'marketingInbox'])->name('marketing.cogm-inbox');

    Route::get('/profile', function () {
        return view('profile.show');
    })->name('profile.show');

    Route::prefix('assistant')->name('assistant.')->group(function () {
        Route::post('/bootstrap', [CostingAssistantController::class, 'bootstrap'])->name('bootstrap');
        Route::post('/chat', [CostingAssistantController::class, 'chat'])->name('chat');
        Route::post('/inspect-file', [CostingAssistantController::class, 'inspectFile'])->name('inspect-file');
        Route::post('/partlist-project/preview', [CostingAssistantController::class, 'previewPartlistProject'])->name('partlist-project.preview');
        Route::post('/partlist-project/create', [CostingAssistantController::class, 'createPartlistProject'])->name('partlist-project.create');
    });

    // ── DASHBOARD ─────────────────────────────────────────────────────────────
    Route::middleware('permission:dashboard')->group(function () {
        Route::get('/', [CostingController::class, 'dashboard'])->name('dashboard');
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
        Route::get('/sla-performance', SlaPerformanceController::class)->name('sla-performance');
    });

    // ── DATABASE ──────────────────────────────────────────────────────────────
    Route::middleware('permission:database')->group(function () {
        // Rate & Kurs
        Route::get('/database/rate-kurs', [ReportController::class, 'rateKurs'])->name('rate-kurs');
        Route::post('/database/rate-kurs', [ReportController::class, 'storeExchangeRate'])->name('rate-kurs.store');
        Route::delete('/database/rate-kurs/{id}', [ReportController::class, 'destroyExchangeRate'])->name('rate-kurs.destroy');

        // Unpriced
        Route::get('/database/unpriced-parts', [ReportController::class, 'unpricedParts'])->name('unpriced-parts');

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
    });

    // ── INPUT DATA ────────────────────────────────────────────────────────────
    Route::middleware('permission:input_data')->group(function () {
        // Form Costing
        Route::get('/form', [CostingController::class, 'form'])->name('form');
        Route::post('/costing/store', [CostingController::class, 'store'])->name('costing.store');
        Route::get('/costing/draft', [CostingDraftController::class, 'show'])->name('costing.draft.show');
        Route::post('/costing/draft', [CostingDraftController::class, 'store'])->name('costing.draft.store');
        Route::delete('/costing/draft', [CostingDraftController::class, 'destroy'])->name('costing.draft.destroy');
        Route::post('/costing/material-quick-update', [CostingController::class, 'quickUpdateMaterial'])->name('costing.material-quick-update');
        Route::post('/costing/material-recalculate', [CostingController::class, 'recalculateMaterial'])->name('costing.material-recalculate');
        Route::get('/costing/store', function () {
            return redirect(route('form', [], false))
                ->with('warning', 'Halaman simpan tidak bisa dibuka langsung. Silakan simpan data dari Form Costing.');
        })->name('costing.store.get');
        Route::get('/costing/import-partlist', fn () => redirect()->route('form'))->name('costing.import-partlist.get');
        Route::post('/costing/import-partlist', [CostingController::class, 'importPartlist'])->name('costing.import-partlist');
        Route::get('/costing/import-cogm', fn () => redirect()->route('form'))->name('costing.import-cogm.get');
        Route::post('/costing/import-cogm', [CostingController::class, 'importCogm'])->name('costing.import-cogm');
        Route::post('/costing/import-cogm/preview', [CostingController::class, 'previewCogm'])->name('costing.import-cogm.preview');
        Route::post('/costing/import-partlist/preview', [CostingController::class, 'previewPartlist'])->name('costing.import-partlist.preview');
        Route::post('/costing/import-umh/preview', [CostingController::class, 'previewUmh'])->name('costing.import-umh.preview');
        Route::post('/costing/imports/{importRun}/rollback', [ImportSafetyController::class, 'rollback'])->name('costing.imports.rollback');
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
    });

    // ── USER MANAGEMENT (admin only) ──────────────────────────────────────────
    Route::middleware('permission:user_management')->group(function () {
        Route::get('/system-center', [SystemCenterController::class, 'index'])->name('system-center.index');
        Route::post('/system-center/announcements', [SystemCenterController::class, 'storeAnnouncement'])->name('system-center.announcements.store');
        Route::delete('/system-center/announcements/{announcement}', [SystemCenterController::class, 'destroyAnnouncement'])->name('system-center.announcements.destroy');
        Route::post('/system-center/delegations', [SystemCenterController::class, 'storeDelegation'])->name('system-center.delegations.store');
        Route::delete('/system-center/delegations/{delegation}', [SystemCenterController::class, 'destroyDelegation'])->name('system-center.delegations.destroy');
        Route::get('/operations-center', [OperationsCenterController::class, 'index'])->name('operations.index');
        Route::post('/operations-center/releases', [OperationsCenterController::class, 'storeRelease'])->name('operations.releases.store');
        Route::patch('/operations-center/releases/{release}', [OperationsCenterController::class, 'updateRelease'])->name('operations.releases.update');
        Route::post('/operations-center/releases/{release}/checks', [OperationsCenterController::class, 'storeCheck'])->name('operations.checks.store');
        Route::patch('/operations-center/checks/{check}', [OperationsCenterController::class, 'updateCheck'])->name('operations.checks.update');
        Route::post('/operations-center/holidays', [OperationsCenterController::class, 'storeHoliday'])->name('operations.holidays.store');
        Route::delete('/operations-center/holidays/{holiday}', [OperationsCenterController::class, 'destroyHoliday'])->name('operations.holidays.destroy');
        Route::post('/operations-center/backups', [OperationsCenterController::class, 'createBackup'])->name('operations.backups.store');
        Route::post('/operations-center/backups/{backup}/verify', [OperationsCenterController::class, 'verifyBackup'])->name('operations.backups.verify');
        Route::get('/operations-center/backups/{backup}/download', [OperationsCenterController::class, 'downloadBackup'])->name('operations.backups.download');
        Route::post('/operations-center/backups/{backup}/restore', [OperationsCenterController::class, 'restoreBackup'])->name('operations.backups.restore');
        Route::get('/permissions', [AuthController::class, 'permissions'])->name('permissions');
        Route::post('/permissions', [AuthController::class, 'storeUser'])->name('permissions.store');
        Route::post('/permissions/update-access', [AuthController::class, 'updatePermission'])->name('permissions.update-access');
        Route::put('/permissions/{id}', [AuthController::class, 'updateUser'])->name('permissions.update');
        Route::delete('/permissions/{id}', [AuthController::class, 'destroyUser'])->name('permissions.destroy');

        Route::get('/assistant-training', [CostingAssistantController::class, 'index'])->name('assistant.training');
        Route::post('/assistant-training/topics', [CostingAssistantController::class, 'storeTopic'])->name('assistant.topics.store');
        Route::put('/assistant-training/topics/{topic}', [CostingAssistantController::class, 'updateTopic'])->name('assistant.topics.update');
        Route::delete('/assistant-training/topics/{topic}', [CostingAssistantController::class, 'destroyTopic'])->name('assistant.topics.destroy');
        Route::post('/assistant-training/rules', [CostingAssistantController::class, 'storeRule'])->name('assistant.rules.store');
        Route::put('/assistant-training/rules/{rule}', [CostingAssistantController::class, 'updateRule'])->name('assistant.rules.update');
        Route::delete('/assistant-training/rules/{rule}', [CostingAssistantController::class, 'destroyRule'])->name('assistant.rules.destroy');
        Route::post('/assistant-training/templates', [CostingAssistantController::class, 'storeTemplate'])->name('assistant.templates.store');
        Route::put('/assistant-training/templates/{template}', [CostingAssistantController::class, 'updateTemplate'])->name('assistant.templates.update');
        Route::delete('/assistant-training/templates/{template}', [CostingAssistantController::class, 'destroyTemplate'])->name('assistant.templates.destroy');
    });
});

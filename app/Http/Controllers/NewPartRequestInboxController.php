<?php

namespace App\Http\Controllers;

use App\Models\DocumentRevision;
use App\Models\Customer;
use App\Services\TrackingDocument\TrackingDocumentUnpricedPartService;
use Illuminate\Http\Request;
use App\Support\BusinessCategoryContext;

class NewPartRequestInboxController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(in_array((string) ($request->user()?->role ?? ''), [
            'admin', 'admin_costing', 'editor', 'coordinator_costing',
        ], true), 403);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'active');
        if (!in_array($status, ['active', 'history', 'all'], true)) {
            $status = 'active';
        }

        $projects = DocumentRevision::query()
            ->with([
                'project.product',
                'plant',
                'costingData.customer',
            ])
            ->withCount([
                'unpricedParts as open_unpriced_count' => fn ($query) => $query->whereNull('resolved_at'),
                'unpricedParts as ready_to_submit_count' => fn ($query) => $query
                    ->whereNull('resolved_at')
                    ->whereNotNull('new_part_price_imported_at')
                    ->where('detected_price', '>', 0),
                'unpricedParts as completed_npr_count' => fn ($query) => $query
                    ->whereNotNull('resolved_at')
                    ->whereIn('resolution_source', ['new_part_request_import', 'realtime_manual_input', 'realtime_db_lookup']),
            ])
            ->withMax('unpricedParts', 'updated_at')
            ->where(function ($query) {
                $query->whereHas('unpricedParts')->orWhereNotNull('new_part_request_exported_at');
            })
            ->when($status === 'active', fn ($query) => $query
                ->where(function ($active) {
                    $active->whereHas('unpricedParts', fn ($part) => $part->whereNull('resolved_at'))
                        ->orWhere(function ($exported) {
                            $exported->whereNotNull('new_part_request_exported_at')->whereDoesntHave('unpricedParts');
                        });
                }))
            ->when($status === 'history', fn ($query) => $query
                ->whereDoesntHave('unpricedParts', fn ($part) => $part->whereNull('resolved_at'))
                ->whereHas('unpricedParts', fn ($part) => $part->whereNotNull('resolved_at')))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->whereHas('project', function ($project) use ($search) {
                        $project->where('customer', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%")
                            ->orWhere('part_number', 'like', "%{$search}%")
                            ->orWhere('part_name', 'like', "%{$search}%");
                    })->orWhereHas('unpricedParts', function ($part) use ($search) {
                        $part->whereNull('resolved_at')
                            ->where(function ($fields) use ($search) {
                                $fields->where('part_number', 'like', "%{$search}%")
                                    ->orWhere('id_code', 'like', "%{$search}%")
                                    ->orWhere('part_name', 'like', "%{$search}%");
                            });
                    });
                });
            })
            ->orderByDesc('updated_at');
        BusinessCategoryContext::apply($projects);
        $projects=$projects->paginate(15)
            ->withQueryString();

        $totalOpenPartsQuery = DocumentRevision::query()
            ->join('unpriced_parts', 'unpriced_parts.document_revision_id', '=', 'document_revisions.id')
            ->whereNull('unpriced_parts.resolved_at');
        BusinessCategoryContext::apply($totalOpenPartsQuery);
        $totalOpenParts=(int)$totalOpenPartsQuery->count('unpriced_parts.id');

        $customerCodes = Customer::query()->get(['name','code'])
            ->mapWithKeys(fn ($customer) => [mb_strtolower(trim((string) $customer->name)) => $customer->code]);

        return view('new-part-request.inbox', compact('projects', 'search', 'status', 'totalOpenParts', 'customerCodes'));
    }

    public function submit(
        Request $request,
        DocumentRevision $revision,
        TrackingDocumentUnpricedPartService $service
    ) {
        abort_unless(in_array((string) ($request->user()?->role ?? ''), [
            'admin', 'admin_costing', 'editor', 'coordinator_costing',
        ], true), 403);

        try {
            $result = $service->submitImportedPrices($revision, (int) $request->user()->id);
            return back()->with('success', $result['message']);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }
}

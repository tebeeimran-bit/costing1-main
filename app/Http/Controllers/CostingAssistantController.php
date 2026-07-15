<?php

namespace App\Http\Controllers;

use App\Models\CostingAssistantFileTemplate;
use App\Models\CostingAssistantRule;
use App\Models\CostingAssistantTopic;
use App\Services\Assistant\CostingAssistantService;
use App\Services\Assistant\PartlistProjectImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CostingAssistantController extends Controller
{
    public function __construct(
        private readonly CostingAssistantService $assistant,
        private readonly PartlistProjectImportService $partlistProjectImport,
    ) {
    }

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('assistant.index', [
            'topics' => CostingAssistantTopic::query()->latest()->get(),
            'rules' => CostingAssistantRule::query()->orderBy('title')->get(),
            'templates' => CostingAssistantFileTemplate::query()->orderBy('type')->orderBy('name')->get(),
            'conditionTypes' => $this->conditionTypes(),
            'roles' => ['admin', 'admin_costing', 'coordinator_costing', 'marketing', 'editor', 'viewer'],
            'severityOptions' => ['info', 'success', 'warning', 'danger'],
        ]);
    }

    public function bootstrap(Request $request): JsonResponse
    {
        $this->ensureAssistantAccess($request);

        $payload = $request->validate([
            'route' => ['nullable', 'string', 'max:120'],
            'path' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json($this->assistant->bootstrap(
            $payload['route'] ?? $request->route()?->getName(),
            $payload['path'] ?? $request->path(),
            $request->user()?->role
        ));
    }

    public function chat(Request $request): JsonResponse
    {
        $this->ensureAssistantAccess($request);

        $payload = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'route' => ['nullable', 'string', 'max:120'],
            'path' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json($this->assistant->respond(
            $payload['message'],
            $payload['route'] ?? $request->route()?->getName(),
            $payload['path'] ?? $request->path(),
            $request->user()?->role
        ));
    }

    public function inspectFile(Request $request): JsonResponse
    {
        $this->ensureAssistantAccess($request);

        $payload = $request->validate([
            'template_id' => ['nullable', 'integer', 'exists:assistant_file_templates,id'],
            'assistant_file' => ['required', 'file', 'mimes:xlsx,xls,csv,pdf', 'max:20480'],
        ]);

        $template = null;
        if (!empty($payload['template_id'])) {
            $template = CostingAssistantFileTemplate::find($payload['template_id']);
        }

        return response()->json($this->assistant->inspectFile($request->file('assistant_file'), $template));
    }

    public function previewPartlistProject(Request $request): JsonResponse
    {
        $this->ensureAssistantAccess($request);

        $payload = $request->validate([
            'template_id' => ['nullable', 'integer', 'exists:assistant_file_templates,id'],
            'assistant_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ]);

        $template = !empty($payload['template_id'])
            ? CostingAssistantFileTemplate::find($payload['template_id'])
            : null;

        return response()->json($this->partlistProjectImport->preview(
            $request->file('assistant_file'),
            $template?->validation_rules ?? []
        ));
    }

    public function createPartlistProject(Request $request): JsonResponse
    {
        $this->ensureAssistantAccess($request);

        $payload = $request->validate([
            'template_id' => ['nullable', 'integer', 'exists:assistant_file_templates,id'],
            'assistant_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ]);

        $template = !empty($payload['template_id'])
            ? CostingAssistantFileTemplate::find($payload['template_id'])
            : null;

        return response()->json($this->partlistProjectImport->createProject(
            $request->file('assistant_file'),
            $template?->validation_rules ?? []
        ));
    }

    public function storeTopic(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);
        $validated = $this->validateTopic($request);
        CostingAssistantTopic::create($validated);

        return redirect()->route('assistant.training')->with('success', 'Topik assistant berhasil ditambahkan.');
    }

    public function updateTopic(Request $request, CostingAssistantTopic $topic): RedirectResponse
    {
        $this->ensureAdmin($request);
        $topic->update($this->validateTopic($request));

        return redirect()->route('assistant.training')->with('success', 'Topik assistant berhasil diperbarui.');
    }

    public function destroyTopic(Request $request, CostingAssistantTopic $topic): RedirectResponse
    {
        $this->ensureAdmin($request);
        $topic->delete();

        return redirect()->route('assistant.training')->with('success', 'Topik assistant dipindahkan dari knowledge base aktif.');
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);
        CostingAssistantRule::create($this->validateRule($request));

        return redirect()->route('assistant.training')->with('success', 'Rule assistant berhasil ditambahkan.');
    }

    public function updateRule(Request $request, CostingAssistantRule $rule): RedirectResponse
    {
        $this->ensureAdmin($request);
        $rule->update($this->validateRule($request, $rule->id));

        return redirect()->route('assistant.training')->with('success', 'Rule assistant berhasil diperbarui.');
    }

    public function destroyRule(Request $request, CostingAssistantRule $rule): RedirectResponse
    {
        $this->ensureAdmin($request);
        $rule->delete();

        return redirect()->route('assistant.training')->with('success', 'Rule assistant berhasil dihapus.');
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);
        CostingAssistantFileTemplate::create($this->validateTemplate($request));

        return redirect()->route('assistant.training')->with('success', 'Template file berhasil ditambahkan.');
    }

    public function updateTemplate(Request $request, CostingAssistantFileTemplate $template): RedirectResponse
    {
        $this->ensureAdmin($request);
        $template->update($this->validateTemplate($request));

        return redirect()->route('assistant.training')->with('success', 'Template file berhasil diperbarui.');
    }

    public function destroyTemplate(Request $request, CostingAssistantFileTemplate $template): RedirectResponse
    {
        $this->ensureAdmin($request);
        $template->delete();

        return redirect()->route('assistant.training')->with('success', 'Template file berhasil dihapus.');
    }

    private function validateTopic(Request $request): array
    {
        $validated = $request->validate([
            'menu' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:160'],
            'content' => ['required', 'string', 'max:3000'],
            'role' => ['nullable', 'string', 'max:80'],
            'keywords_text' => ['nullable', 'string', 'max:1000'],
            'active' => ['nullable', 'boolean'],
        ]);

        return [
            'menu' => $validated['menu'],
            'title' => $validated['title'],
            'content' => $validated['content'],
            'role' => $validated['role'] ?: null,
            'keywords' => $this->splitLines($validated['keywords_text'] ?? ''),
            'active' => $request->boolean('active'),
        ];
    }

    private function validateRule(Request $request, ?int $ignoreId = null): array
    {
        $uniqueRule = 'unique:assistant_rules,code';
        if ($ignoreId) {
            $uniqueRule .= ',' . $ignoreId;
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:120', $uniqueRule],
            'title' => ['required', 'string', 'max:160'],
            'condition_type' => ['required', 'string', 'max:80'],
            'condition_payload_text' => ['nullable', 'string', 'max:2000'],
            'severity' => ['required', 'in:info,success,warning,danger'],
            'message' => ['required', 'string', 'max:2000'],
            'action_label' => ['nullable', 'string', 'max:120'],
            'action_url' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ]);

        return [
            'code' => $validated['code'],
            'title' => $validated['title'],
            'condition_type' => $validated['condition_type'],
            'condition_payload' => $this->decodeJson($validated['condition_payload_text'] ?? ''),
            'severity' => $validated['severity'],
            'message' => $validated['message'],
            'action_label' => $validated['action_label'] ?: null,
            'action_url' => $validated['action_url'] ?: null,
            'active' => $request->boolean('active'),
        ];
    }

    private function validateTemplate(Request $request): array
    {
        $validated = $request->validate([
            'type' => ['required', 'in:excel,pdf'],
            'name' => ['required', 'string', 'max:160'],
            'required_columns_text' => ['nullable', 'string', 'max:2000'],
            'optional_columns_text' => ['nullable', 'string', 'max:2000'],
            'validation_rules_text' => ['nullable', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
        ]);

        return [
            'type' => $validated['type'],
            'name' => $validated['name'],
            'required_columns' => $this->splitLines($validated['required_columns_text'] ?? ''),
            'optional_columns' => $this->splitLines($validated['optional_columns_text'] ?? ''),
            'validation_rules' => $this->decodeJson($validated['validation_rules_text'] ?? ''),
            'active' => $request->boolean('active'),
        ];
    }

    private function splitLines(string $value): array
    {
        return collect(preg_split('/[\r\n,]+/', $value) ?: [])
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function decodeJson(string $value): ?array
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            abort(422, 'Payload JSON tidak valid.');
        }

        return $decoded;
    }

    private function conditionTypes(): array
    {
        return [
            'always' => 'Selalu tampil',
            'keyword' => 'Jika keyword cocok',
            'route_is' => 'Jika route cocok',
            'role_is' => 'Jika role cocok',
            'unresolved_unpriced_gt' => 'Jika unpriced parts lebih dari nilai',
            'waiting_approval_gt' => 'Jika waiting approval lebih dari nilai',
            'missing_exchange_rate_current_month' => 'Jika rate kurs bulan berjalan kosong',
        ];
    }

    private function ensureAssistantAccess(Request $request): void
    {
        abort_unless(
            in_array($request->user()?->role, ['admin', 'admin_costing'], true),
            403,
            'Costing Assistant hanya tersedia untuk Admin dan Admin Costing.'
        );
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Hanya admin yang dapat training Costing Assistant.');
    }
}

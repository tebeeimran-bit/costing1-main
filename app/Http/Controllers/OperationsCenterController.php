<?php

namespace App\Http\Controllers;

use App\Models\CompanyHoliday;
use App\Models\ReleaseCheck;
use App\Models\ReleaseCycle;
use App\Models\SystemBackup;
use App\Services\Operations\DatabaseBackupService;
use Illuminate\Http\Request;

class OperationsCenterController extends Controller
{
    public function index()
    {
        $releases = ReleaseCycle::with(['checks.tester'])->latest()->limit(12)->get();
        $holidays = CompanyHoliday::orderBy('holiday_date')->get();
        $backups = SystemBackup::latest()->limit(15)->get();

        return view('operations.index', compact('releases', 'holidays', 'backups'));
    }

    public function storeRelease(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'version' => ['nullable', 'string', 'max:40'], 'target_release_at' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $release = ReleaseCycle::create($data + ['created_by' => $request->user()->id]);
        foreach ($this->defaultChecks() as $index => $check) {
            $release->checks()->create($check + ['sort_order' => $index + 1]);
        }

        return back()->with('success', 'Release cycle dan checklist standar berhasil dibuat.');
    }

    public function updateRelease(Request $request, ReleaseCycle $release)
    {
        $data = $request->validate(['status' => ['required', 'in:draft,testing,ready,released,blocked'], 'notes' => ['nullable', 'string', 'max:2000']]);
        if ($data['status'] === 'ready' && $release->checks()->whereNotIn('status', ['pass'])->exists()) {
            return back()->with('error', 'Release belum dapat dinyatakan ready karena masih ada checklist yang belum pass.');
        }
        $release->update($data + ['released_at' => $data['status'] === 'released' ? now() : null]);

        return back()->with('success', 'Status release diperbarui.');
    }

    public function storeCheck(Request $request, ReleaseCycle $release)
    {
        $data = $request->validate(['category' => ['required', 'in:functional,security,data,performance,ux,deployment'], 'title' => ['required', 'string', 'max:160'], 'description' => ['nullable', 'string', 'max:1000']]);
        $release->checks()->create($data + ['sort_order' => ((int) $release->checks()->max('sort_order')) + 1]);

        return back()->with('success', 'Checklist ditambahkan.');
    }

    public function updateCheck(Request $request, ReleaseCheck $check)
    {
        $data = $request->validate(['status' => ['required', 'in:pending,pass,fail,blocked'], 'notes' => ['nullable', 'string', 'max:1000']]);
        $check->update($data + ['tester_id' => $request->user()->id, 'tested_at' => $data['status'] === 'pending' ? null : now()]);

        return back()->with('success', 'Hasil pengujian disimpan.');
    }

    public function storeHoliday(Request $request)
    {
        $data = $request->validate(['holiday_date' => ['required', 'date'], 'name' => ['required', 'string', 'max:120']]);
        CompanyHoliday::updateOrCreate(['holiday_date' => $data['holiday_date']], $data + ['is_active' => true, 'created_by' => $request->user()->id]);

        return back()->with('success', 'Hari libur ditambahkan ke perhitungan SLA.');
    }

    public function destroyHoliday(CompanyHoliday $holiday)
    {
        $holiday->delete();

        return back()->with('success', 'Hari libur dihapus.');
    }

    public function createBackup(Request $request, DatabaseBackupService $service)
    {
        $service->create($request->user()->id, $request->string('notes')->limit(500)->toString());

        return back()->with('success', 'Backup baru berhasil dibuat dan diverifikasi.');
    }

    public function verifyBackup(SystemBackup $backup, DatabaseBackupService $service)
    {
        $valid = $service->verify($backup);

        return back()->with($valid ? 'success' : 'error', $valid ? 'Checksum backup valid.' : 'Backup rusak atau tidak ditemukan.');
    }

    public function downloadBackup(SystemBackup $backup, DatabaseBackupService $service)
    {
        abort_unless($service->verify($backup), 404, 'Backup tidak valid.');

        return response()->download($backup->path, $backup->filename);
    }

    public function restoreBackup(Request $request, SystemBackup $backup, DatabaseBackupService $service)
    {
        $request->validate(['confirmation' => ['required', 'in:RESTORE']]);
        $service->restore($backup, $request->user()->id);

        return redirect()->route('login')->with('success', 'Database berhasil dipulihkan. Silakan masuk kembali.');
    }

    private function defaultChecks(): array
    {
        return [
            ['category' => 'functional', 'title' => 'Critical workflow lulus pengujian'],
            ['category' => 'data', 'title' => 'Migration dan integritas data terverifikasi'],
            ['category' => 'security', 'title' => 'Role dan permission sudah diuji'],
            ['category' => 'performance', 'title' => 'Form Costing dan laporan memenuhi target performa'],
            ['category' => 'ux', 'title' => 'Desktop, tablet, dan mobile telah diperiksa'],
            ['category' => 'deployment', 'title' => 'Backup, rollback plan, dan release notes siap'],
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ImportRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImportSafetyController extends Controller
{
    public function rollback(Request $request, ImportRun $importRun)
    {
        abort_unless($importRun->user_id === $request->user()->id || $request->user()->role === 'admin', 403);
        if ($importRun->status !== 'applied' || ! is_array($importRun->before_snapshot)) {
            return back()->with('error', 'Import ini tidak dapat di-rollback.');
        }
        DB::transaction(function () use ($importRun, $request) {
            if (in_array($importRun->type, ['cogm', 'partlist'], true)) {
                $rows = $importRun->before_snapshot['materials'] ?? [];
                DB::table('material_breakdowns')->where('costing_data_id', $importRun->costing_data_id)->delete();
                foreach (array_chunk($rows, 200) as $chunk) {
                    if ($chunk) {
                        DB::table('material_breakdowns')->insert($chunk);
                    }
                }
                if (array_key_exists('material_cost', $importRun->before_snapshot)) {
                    DB::table('costing_data')->where('id', $importRun->costing_data_id)->update(['material_cost' => $importRun->before_snapshot['material_cost']]);
                }
            } elseif ($importRun->type === 'umh') {
                DB::table('costing_data')->where('id', $importRun->costing_data_id)->update([
                    'cycle_times' => json_encode($importRun->before_snapshot['cycle_times'] ?? [], JSON_THROW_ON_ERROR),
                ]);
            } else {
                abort(422, 'Jenis import ini belum mendukung rollback.');
            }
            $importRun->update(['status' => 'rolled_back', 'rolled_back_at' => now(), 'rolled_back_by' => $request->user()->id]);
        });

        return redirect()->route('form', ['id' => $importRun->costing_data_id])->with('success', 'Import berhasil di-rollback ke kondisi sebelumnya.');
    }
}

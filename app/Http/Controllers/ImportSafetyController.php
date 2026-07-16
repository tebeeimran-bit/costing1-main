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
        $rows = $importRun->before_snapshot['materials'] ?? [];
        DB::transaction(function () use ($importRun, $rows, $request) {
            DB::table('material_breakdowns')->where('costing_data_id', $importRun->costing_data_id)->delete();
            foreach (array_chunk($rows, 200) as $chunk) {
                if ($chunk) {
                    DB::table('material_breakdowns')->insert($chunk);
                }
            }
            if (array_key_exists('material_cost', $importRun->before_snapshot)) {
                DB::table('costing_data')->where('id', $importRun->costing_data_id)->update(['material_cost' => $importRun->before_snapshot['material_cost']]);
            }
            $importRun->update(['status' => 'rolled_back', 'rolled_back_at' => now(), 'rolled_back_by' => $request->user()->id]);
        });

        return redirect()->route('form', ['id' => $importRun->costing_data_id])->with('success', 'Import berhasil di-rollback ke kondisi sebelumnya.');
    }
}

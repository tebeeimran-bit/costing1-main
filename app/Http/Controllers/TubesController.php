<?php

namespace App\Http\Controllers;

use App\Models\Tube;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TubesController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $tubes = Tube::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('tube_code', 'like', "%{$search}%")
                        ->orWhere('tube_name', 'like', "%{$search}%")
                        ->orWhere('spec', 'like', "%{$search}%")
                        ->orWhere('supplier', 'like', "%{$search}%");
                });
            })
            ->orderBy('tube_code')
            ->paginate(20)
            ->withQueryString();

        return view('database.tubes', compact('tubes', 'search'));
    }

    public function store(Request $request)
    {
        Tube::create($this->validated($request));

        return back()->with('success', 'Data Tubes berhasil ditambahkan.');
    }

    public function update(Request $request, Tube $tube)
    {
        $tube->update($this->validated($request, $tube->id));

        return back()->with('success', 'Data Tubes berhasil diperbarui.');
    }

    public function destroy(Tube $tube)
    {
        $tube->delete();

        return back()->with('success', 'Data Tubes berhasil dihapus.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $units = ['pcs', 'meter', 'mm', 'set', 'unit'];

        return $request->validate([
            'tube_code' => [
                'required',
                'string',
                'max:120',
                Rule::unique('tubes', 'tube_code')->ignore($ignoreId),
            ],
            'tube_name' => ['nullable', 'string', 'max:255'],
            'spec' => ['nullable', 'string', 'max:255'],
            'material_type' => ['nullable', 'string', 'max:255'],
            'diameter' => ['nullable', 'numeric'],
            'thickness' => ['nullable', 'numeric'],
            'length' => ['nullable', 'numeric'],
            'unit' => ['required', Rule::in($units)],
            'price' => ['required', 'numeric', 'min:0'],
            'price_unit' => ['required', Rule::in($units)],
            'currency' => ['required', 'string', 'max:10'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'effective_date' => ['nullable', 'date'],
            'is_estimate' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}

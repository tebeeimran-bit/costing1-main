<?php

namespace App\Http\Controllers;

use App\Support\BusinessCategoryContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BusinessCategoryContextController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_category_id' => ['nullable', Rule::exists('business_categories', 'id')],
        ]);

        $categoryId = $validated['business_category_id'] ?? null;
        if ($categoryId) {
            $request->session()->put(BusinessCategoryContext::SESSION_KEY, (int) $categoryId);
        } else {
            $request->session()->forget(BusinessCategoryContext::SESSION_KEY);
        }
        BusinessCategoryContext::resetCache();

        return back()->with('success', 'Business Category aktif berhasil diubah.');
    }
}

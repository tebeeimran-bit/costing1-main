<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DocumentProject;
use App\Models\Material;
use App\Models\RolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->merge(['q' => trim((string) $request->query('q', ''))]);
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $query = $validated['q'];
        $like = '%' . $query . '%';
        $role = (string) ($request->user()?->role ?? 'viewer');

        $results = collect();

        DocumentProject::query()
            ->where(function ($builder) use ($like) {
                $builder->where('part_number', 'like', $like)
                    ->orWhere('part_name', 'like', $like)
                    ->orWhere('customer', 'like', $like)
                    ->orWhere('model', 'like', $like);
            })
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->each(function (DocumentProject $project) use ($results) {
                $results->push([
                    'type' => 'Project',
                    'title' => trim(($project->part_number ?: 'Tanpa Part Number') . ' — ' . ($project->part_name ?: 'Project Costing')),
                    'description' => collect([$project->customer, $project->model])->filter()->implode(' · '),
                    'url' => route('project', ['search' => $project->part_number ?: $project->customer], false),
                ]);
            });

        Customer::query()->where(function ($builder) use ($like) {
            $builder->where('name', 'like', $like)->orWhere('code', 'like', $like);
        })->limit(5)->get()->each(function (Customer $customer) use ($results) {
            $results->push([
                'type' => 'Customer',
                'title' => $customer->name,
                'description' => $customer->code ? 'Kode ' . $customer->code : 'Customer',
                'url' => route('project', ['search' => $customer->name], false),
            ]);
        });

        if (RolePermission::hasAccess($role, 'database')) {
            Material::query()->where(function ($builder) use ($like) {
                $builder->where('material_code', 'like', $like)
                    ->orWhere('material_description', 'like', $like)
                    ->orWhere('maker', 'like', $like);
            })->limit(6)->get()->each(function (Material $material) use ($results) {
                $results->push([
                    'type' => 'Material',
                    'title' => trim(($material->material_code ?: '-') . ' — ' . ($material->material_description ?: 'Material')),
                    'description' => collect([$material->maker, $material->currency])->filter()->implode(' · '),
                    'url' => route('database.parts', ['search' => $material->material_code], false),
                ]);
            });
        }

        return response()->json(['results' => $results->take(18)->values()]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends ApiController
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $brands = Brand::query()
            ->when($q !== '', fn($qb) => $qb->where('name', 'ilike', '%' . $q . '%'))
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 20));

        return response()->json($brands);
    }

    public function show(Brand $brand)
    {
        return response()->json($brand);
    }

    public function store(Request $request)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:bands,name'],
            'slug' => ['required', 'string', 'max:255', 'unique:brands,slug', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string'],
        ]);

        return response()->json(Brand::create($data), 201);
    }

    public function update(Request $request, Brand $brand)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('brands', 'name')->ignore($brand->id)],
            'slug' => ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'], Rule::unique('brands', 'slug')->ignore($brand->id),
            'description' => ['nullable', 'string'],
        ]);

        $brand->fill($data)->save();

        return response()->json($brand->fresh());
    }

    public function destroy(Request $request, Brand $brand)
    {
        $this->requireAdmin($request);

        $brand->delete();

        return response()->json(['ok' => true]);
    }
}

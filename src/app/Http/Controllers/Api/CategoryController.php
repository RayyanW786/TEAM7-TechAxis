<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends ApiController
{
    public function index(Request $request)
    {
        $parentSlug = $request->query('parent_slug');

        $rows = DB::select(
            'select * from fn_list_categories_by_parent_slug(?)',
            [$parentSlug]
        );

        return response()->json([
            'parent_slug' => $parentSlug,
            'items' => $rows,
        ]);
    }

    public function show(Category $category)
    {
        $category->load('children');

        return response()->json($category);
    }

    public function store(Request $request)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return response()->json(Category::create($data), 201);
    }

    public function update(Request $request, Category $category)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category->fill($data)->save();

        return response()->json($category->fresh());
    }

    public function destroy(Request $request, Category $category)
    {
        $this->requireAdmin($request);

        $category->delete();

        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'distinct'],
        ]);

        Category::reorderSiblings($data['parent_id'] ?? null, $data['category_ids']);

        return response()->json(['ok' => true]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;

class ProductImageController extends ApiController
{
    public function index(Product $product)
    {
        return response()->json([
            'product_id' => $product->id,
            'items' => $product->imagesUsingPgFn(),
        ]);
    }

    public function store(Request $request, Product $product)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'url' => ['required', 'string'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $image = ProductImage::create([
            'product_id' => $product->id,
            'url' => $data['url'],
            'alt_text' => $data['alt_text'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return response()->json($image, 201);
    }

    public function destroy(Request $request, Product $product, ProductImage $image)
    {
        $this->requireAdmin($request);

        abort_unless((int) $image->product_id === (int) $product->id, 404);

        $image->delete();

        return response()->json(['ok' => true]);
    }

    public function setPrimary(Request $request, Product $product, ProductImage $image)
    {
        $this->requireAdmin($request);

        abort_unless((int) $image->product_id === (int) $product->id, 404);

        ProductImage::setAsPrimary($product->id, $image->id);

        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, Product $product)
    {
        $this->requireAdmin($request);

        $data = $request->validate([
            'image_ids' => ['required', 'array', 'min:1'],
            'image_ids.*' => ['integer', 'distinct'],
        ]);

        ProductImage::reorder($product->id, $data['image_ids']);

        return response()->json(['ok' => true]);
    }
}

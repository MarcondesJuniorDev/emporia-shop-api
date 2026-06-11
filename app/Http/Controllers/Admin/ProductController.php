<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Cria um novo produto.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'required|string|max:255|unique:products,name',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'is_active' => $validated['is_active'] ?? true,
            'image_path' => $imagePath,
        ]);

        return (new ProductResource($product->load('category')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Atualiza um produto existente.
     */
    public function update(Product $product, Request $request): ProductResource
    {
        $validated = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'required|string|max:255|unique:products,name,'.$product->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = $product->image_path;
        if ($request->hasFile('image')) {
            // Remove a imagem antiga se existir
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product->update([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'is_active' => $validated['is_active'] ?? $product->is_active,
            'image_path' => $imagePath,
        ]);

        return new ProductResource($product->load('category'));
    }

    /**
     * Remove um produto.
     */
    public function destroy(Product $product): JsonResponse
    {
        // Remove a imagem associada se existir
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return response()->json([
            'message' => 'Produto excluído com sucesso.',
        ]);
    }
}

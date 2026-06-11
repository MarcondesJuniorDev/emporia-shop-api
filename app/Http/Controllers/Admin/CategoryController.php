<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Cria uma nova categoria.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        Cache::forget('public_categories');

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Atualiza uma categoria existente.
     */
    public function update(Category $category, Request $request): CategoryResource
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,'.$category->id,
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        Cache::forget('public_categories');

        return new CategoryResource($category);
    }

    /**
     * Remove uma categoria.
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        Cache::forget('public_categories');

        return response()->json([
            'message' => 'Categoria excluída com sucesso.',
        ]);
    }
}

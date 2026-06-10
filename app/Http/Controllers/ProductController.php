<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Lista os produtos da vitrine com paginação, busca e filtros.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Criamos a query base trazendo o relacionamento de categoria para evitar o problema N+1
        $query = Product::with('category')->where('is_active', true);

        // Filtro por busca textual (Nome ou Descrição)
        if ($request->has('search') && $request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        // Filtro por slug de categoria específica
        if ($request->has('category') && $request->filled('category')) {
            $categorySlug = $request->input('category');
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        // Ordenação flexível (Padrão: mais recentes primeiro)
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if (in_array($sortBy, ['price', 'created_at', 'name'])) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Paginação profissional (12 produtos por página para a grade do frontend)
        $products = $query->paginate($request->input('per_page', 12));

        // Retorna a coleção formatada com os links de paginação nativos do Laravel
        return ProductResource::collection($products);
    }

    /**
     * Exibe os detalhes de um único produto (Página interna do e-commerce).
     */
    public function show(Product $product): ProductResource
    {
        if (!$product->is_active) {
            abort(404, 'Produto indisponível.');
        }

        return new ProductResource($product->load('category'));
    }

    /**
     * Retorna a lista de categorias públicas para alimentar o menu lateral da vitrine.
     */
    public function categories(): AnonymousResourceCollection
    {
        $categories = Category::orderBy('name')->get();
        return CategoryResource::collection($categories);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListProductsRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Lista os produtos da vitrine com paginação, busca e filtros.
     */
    public function index(ListProductsRequest $request): AnonymousResourceCollection
    {
        $products = Product::with('category')
            ->active()
            ->search($request->input('search'))
            ->byCategory($request->input('category'))
            ->sort($request->input('sort_by'), $request->input('sort_order'))
            ->paginate($request->input('per_page', 12));

        return ProductResource::collection($products);
    }

    /**
     * Exibe os detalhes de um único produto (Página interna do e-commerce).
     */
    public function show(Product $product): ProductResource
    {
        if (! $product->is_active) {
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

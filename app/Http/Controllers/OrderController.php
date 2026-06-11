<?php

namespace App\Http\Controllers;

use App\Events\OrderPlaced;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * Lista os pedidos do usuário autenticado.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::with('items.product')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 10));

        return OrderResource::collection($orders);
    }

    /**
     * Exibe os detalhes de um pedido específico.
     */
    public function show(Order $order, Request $request): OrderResource
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Acesso não autorizado a este pedido.');
        }

        return new OrderResource($order->load('items.product'));
    }

    /**
     * Cria um novo pedido (Checkout).
     */
    public function store(CheckoutRequest $request): JsonResponse
    {
        $user = $request->user();
        $itemsData = $request->input('items');
        $shippingAddress = $request->input('shipping_address');

        // Iniciamos uma transação no banco de dados para garantir atomicidade
        $order = DB::transaction(function () use ($user, $itemsData, $shippingAddress) {
            $totalAmount = 0.0;
            $itemsToCreate = [];

            foreach ($itemsData as $item) {
                // Selecionamos o produto aplicando Lock para impedir alterações concorrentes no estoque
                $product = Product::lockForUpdate()->find($item['product_id']);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => 'Um ou mais produtos selecionados não foram encontrados.',
                    ]);
                }

                if (! $product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => "O produto {$product->name} não está ativo e não pode ser comprado.",
                    ]);
                }

                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "Estoque insuficiente para o produto {$product->name}. Disponível: {$product->stock}.",
                    ]);
                }

                // Decrementa o estoque de forma atômica
                $product->decrement('stock', $item['quantity']);

                $subtotal = $product->price * $item['quantity'];
                $totalAmount += $subtotal;

                $itemsToCreate[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ];
            }

            // Cria o pedido principal
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'shipping_address' => $shippingAddress,
            ]);

            // Cria os itens associados ao pedido
            foreach ($itemsToCreate as $itemData) {
                $order->items()->create($itemData);
            }

            return $order;
        });

        event(new OrderPlaced($order));

        return (new OrderResource($order->load('items.product')))
            ->response()
            ->setStatusCode(201);
    }
}

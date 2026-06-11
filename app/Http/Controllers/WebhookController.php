<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Processa atualizações de pagamento enviadas pelo gateway de pagamento.
     */
    public function handlePayment(Request $request): JsonResponse
    {
        // Validação de token de segurança básico
        $webhookToken = $request->header('X-Webhook-Token');
        $expectedToken = config('services.payment_gateway.webhook_token', 'emporia-secret-token');

        if ($webhookToken !== $expectedToken) {
            Log::warning('Tentativa de acesso não autorizada ao webhook de pagamentos.', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'status' => 'required|string|in:approved,declined,refunded',
        ]);

        $orderId = $request->input('order_id');
        $status = $request->input('status');

        DB::transaction(function () use ($orderId, $status) {
            // Buscando o pedido com lock para evitar concorrência
            $order = Order::lockForUpdate()->find($orderId);

            if ($status === 'approved') {
                $order->update(['status' => 'paid']);
                Log::info("Pedido #{$order->id} foi pago com sucesso.");
            } elseif ($status === 'declined') {
                $order->update(['status' => 'cancelled']);

                // Devolvemos o estoque de forma atômica se a transação falhar
                foreach ($order->items as $item) {
                    $item->product->increment('stock', $item->quantity);
                }

                Log::info("Pedido #{$order->id} foi recusado e o estoque foi devolvido.");
            } elseif ($status === 'refunded') {
                $order->update(['status' => 'cancelled']);

                // Devolvemos o estoque em caso de estorno
                foreach ($order->items as $item) {
                    $item->product->increment('stock', $item->quantity);
                }

                Log::info("Pedido #{$order->id} foi estornado e o estoque foi devolvido.");
            }
        });

        return response()->json(['message' => 'Webhook processado com sucesso.']);
    }
}

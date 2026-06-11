<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        Log::info(sprintf(
            'E-mail de confirmação de pedido enviado para %s relativo ao Pedido #%d no valor de R$ %s.',
            $event->order->user->email,
            $event->order->id,
            number_format($event->order->total_amount, 2, ',', '.')
        ));
    }
}

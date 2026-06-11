<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product->name,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'price_formatted' => 'R$ '.number_format($this->price, 2, ',', '.'),
            'subtotal' => $this->quantity * $this->price,
            'subtotal_formatted' => 'R$ '.number_format($this->quantity * $this->price, 2, ',', '.'),
        ];
    }
}

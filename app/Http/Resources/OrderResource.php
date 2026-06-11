<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'total_amount_formatted' => 'R$ '.number_format($this->total_amount, 2, ',', '.'),
            'shipping_address' => $this->shipping_address,
            'created_at' => $this->created_at,
            'items' => OrderItemResource::collection($this->whenLoaded('items') ?: $this->items),
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'number' => $this->number,
            'created_at' => $this->created_at,
            'status' => ['value' => $this->status->value, 'label' => $this->status->getLabel()],
            'payment_status' => ['value' => $this->payment_status->value, 'label' => $this->payment_status->getLabel()],
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'delivery_method' => ['value' => $this->delivery_method->value, 'label' => $this->delivery_method->getLabel()],
            'delivery_address' => $this->delivery_address,
            'payment_method' => ['value' => $this->payment_method->value, 'label' => $this->payment_method->getLabel()],
            'subtotal' => (float) $this->subtotal,
            'delivery_cost' => (float) $this->delivery_cost,
            'total' => (float) $this->total,
            'comment' => $this->comment,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn (OrderItem $item) => [
                'product_id' => $item->product_id,
                'brand' => $item->brand_name,
                'article' => $item->article,
                'name' => $item->name,
                'price' => (float) $item->price,
                'quantity' => $item->quantity,
                'delivery_days' => $item->delivery_days,
                'status' => ['value' => $item->status->value, 'label' => $item->status->getLabel()],
            ])),
        ];
    }
}

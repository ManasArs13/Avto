<?php

namespace App\Http\Resources;

use App\Enums\SupplierType;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Поставщика покупателю не раскрываем: только цена, наличие и срок.
 *
 * @mixin Offer
 */
class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'price' => $this->sale_price ?? $this->salePrice(),
            'quantity' => $this->quantity,
            'multiplicity' => $this->multiplicity,
            'delivery_days' => $this->totalDeliveryDays(),
            'in_stock' => $this->supplier->type === SupplierType::Warehouse,
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'article' => $this->article,
            'name' => $this->name,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'description' => $this->description,
            'specs' => $this->specs ?? (object) [],
            'images' => $this->images ?? [],
            // Заполняются в CatalogSearch, если товар пришёл из поиска.
            'min_price' => $this->whenHas('min_price'),
            'min_delivery_days' => $this->whenHas('min_delivery_days'),
            'offers_count' => $this->whenHas('offers_count'),
            'offers' => OfferResource::collection($this->whenLoaded('offers')),
        ];
    }
}

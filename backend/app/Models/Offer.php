<?php

namespace App\Models;

use App\Services\PriceCalculator;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['supplier_id', 'product_id', 'purchase_price', 'quantity', 'delivery_days', 'multiplicity'])]
class Offer extends Model
{
    protected function casts(): array
    {
        return ['purchase_price' => 'decimal:2'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Цена продажи с учётом правил наценки.
     */
    public function salePrice(): float
    {
        return app(PriceCalculator::class)->forOffer($this);
    }

    /**
     * Итоговый срок для покупателя: срок из прайса + собственный срок поставщика.
     */
    public function totalDeliveryDays(): int
    {
        return $this->delivery_days + ($this->supplier?->delivery_days ?? 0);
    }
}

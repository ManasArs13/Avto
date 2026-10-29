<?php

namespace App\Models;

use App\Services\PriceCalculator;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'supplier_id', 'brand_id', 'price_from', 'price_to', 'percent', 'priority', 'is_active'])]
class MarkupRule extends Model
{
    protected function casts(): array
    {
        return [
            'price_from' => 'decimal:2',
            'price_to' => 'decimal:2',
            'percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => PriceCalculator::flushCache());
        static::deleted(fn () => PriceCalculator::flushCache());
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function matches(int $supplierId, int $brandId, float $purchasePrice): bool
    {
        return ($this->supplier_id === null || $this->supplier_id === $supplierId)
            && ($this->brand_id === null || $this->brand_id === $brandId)
            && $purchasePrice >= (float) $this->price_from
            && ($this->price_to === null || $purchasePrice < (float) $this->price_to);
    }
}

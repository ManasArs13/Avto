<?php

namespace App\Models;

use App\Enums\SupplierType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'type', 'delivery_days', 'settings', 'is_active'])]
class Supplier extends Model
{
    /**
     * Колонки прайса по умолчанию (номер колонки с 0). Переопределяются в settings.columns.
     */
    public const DEFAULT_COLUMNS = [
        'brand' => 0,
        'article' => 1,
        'name' => 2,
        'price' => 3,
        'quantity' => 4,
        'delivery_days' => 5,
    ];

    protected function casts(): array
    {
        return [
            'type' => SupplierType::class,
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function priceImports(): HasMany
    {
        return $this->hasMany(PriceImport::class);
    }

    public function priceColumns(): array
    {
        return array_merge(self::DEFAULT_COLUMNS, $this->settings['columns'] ?? []);
    }
}

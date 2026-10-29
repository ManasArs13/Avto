<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SupplierType: string implements HasColor, HasLabel
{
    case Warehouse = 'warehouse';
    case PriceFile = 'price_file';
    case Api = 'api';

    public function getLabel(): string
    {
        return match ($this) {
            self::Warehouse => 'Свой склад',
            self::PriceFile => 'Прайс-файл',
            self::Api => 'API поставщика',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Warehouse => 'success',
            self::PriceFile => 'info',
            self::Api => 'warning',
        };
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderItemStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Ordered = 'ordered';
    case InStock = 'in_stock';
    case Issued = 'issued';
    case Refused = 'refused';
    case Returned = 'returned';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Новая',
            self::Ordered => 'Заказана',
            self::InStock => 'На складе',
            self::Issued => 'Выдана',
            self::Refused => 'Отказ поставщика',
            self::Returned => 'Возврат',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Ordered => 'warning',
            self::InStock => 'success',
            self::Issued => 'gray',
            self::Refused => 'danger',
            self::Returned => 'danger',
        };
    }
}

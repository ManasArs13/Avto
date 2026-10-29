<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Ordered = 'ordered';
    case Ready = 'ready';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Новый',
            self::Confirmed => 'Подтверждён',
            self::Ordered => 'Заказан у поставщика',
            self::Ready => 'Готов к выдаче',
            self::Shipped => 'Отправлен',
            self::Completed => 'Выполнен',
            self::Cancelled => 'Отменён',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Confirmed => 'primary',
            self::Ordered => 'warning',
            self::Ready => 'success',
            self::Shipped => 'success',
            self::Completed => 'gray',
            self::Cancelled => 'danger',
        };
    }
}

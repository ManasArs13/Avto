<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DeliveryMethod: string implements HasColor, HasLabel
{
    case Pickup = 'pickup';
    case Courier = 'courier';
    case Cdek = 'cdek';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pickup => 'Самовывоз',
            self::Courier => 'Курьер',
            self::Cdek => 'СДЭК',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pickup => 'gray',
            self::Courier => 'info',
            self::Cdek => 'success',
        };
    }
}

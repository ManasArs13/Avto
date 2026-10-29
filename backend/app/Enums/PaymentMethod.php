<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasColor, HasLabel
{
    case Card = 'card';
    case OnReceipt = 'on_receipt';
    case Invoice = 'invoice';

    public function getLabel(): string
    {
        return match ($this) {
            self::Card => 'Картой онлайн',
            self::OnReceipt => 'При получении',
            self::Invoice => 'Счёт для юрлиц',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Card => 'info',
            self::OnReceipt => 'gray',
            self::Invoice => 'warning',
        };
    }
}

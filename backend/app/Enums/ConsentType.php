<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ConsentType: string implements HasLabel
{
    /** Согласие на обработку персональных данных (152-ФЗ, ст. 9). */
    case PersonalData = 'personal_data';
    /** Согласие на получение рекламных рассылок (38-ФЗ «О рекламе», ст. 18). */
    case Marketing = 'marketing';

    public function getLabel(): string
    {
        return match ($this) {
            self::PersonalData => 'Обработка персональных данных',
            self::Marketing => 'Рекламные рассылки',
        };
    }

    /** Документ, на который даётся согласие. */
    public function documentSlug(): string
    {
        return match ($this) {
            self::PersonalData => 'personal-data-consent',
            self::Marketing => 'marketing-consent',
        };
    }
}

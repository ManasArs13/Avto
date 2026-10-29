<?php

namespace App\Filament\Resources\CarModels\Pages;

use App\Filament\Resources\CarModels\CarModelResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

// Для роли «Только просмотр»: форма открывается заблокированной.
class ViewCarModel extends ViewRecord
{
    protected static string $resource = CarModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

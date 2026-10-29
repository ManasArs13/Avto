<?php

namespace App\Filament\Resources\CarMakes\Pages;

use App\Filament\Resources\CarMakes\CarMakeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCarMakes extends ManageRecords
{
    protected static string $resource = CarMakeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

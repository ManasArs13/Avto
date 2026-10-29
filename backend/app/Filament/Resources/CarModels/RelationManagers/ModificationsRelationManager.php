<?php

namespace App\Filament\Resources\CarModels\RelationManagers;

use App\Filament\Concerns\ReadOnlyForViewers;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModificationsRelationManager extends RelationManager
{
    use ReadOnlyForViewers;

    protected static string $relationship = 'modifications';

    protected static ?string $title = 'Модификации';

    protected static ?string $modelLabel = 'модификация';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->placeholder('2.5 (181 л.с.)')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('engine_code')
                    ->label('Код двигателя'),
                TextInput::make('engine_volume')
                    ->label('Объём, л')
                    ->numeric(),
                TextInput::make('power_hp')
                    ->label('Мощность, л.с.')
                    ->numeric(),
                Select::make('fuel')
                    ->label('Топливо')
                    ->options(['Бензин' => 'Бензин', 'Дизель' => 'Дизель', 'Гибрид' => 'Гибрид', 'Электро' => 'Электро']),
                TextInput::make('year_from')
                    ->label('Год с')
                    ->numeric(),
                TextInput::make('year_to')
                    ->label('Год по')
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Модификация')
                    ->weight('bold'),
                TextColumn::make('engine_code')
                    ->label('Двигатель'),
                TextColumn::make('fuel')
                    ->label('Топливо'),
                TextColumn::make('years')
                    ->label('Годы')
                    ->state(fn ($record) => $record->year_from.' – '.($record->year_to ?? 'н.в.')),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\CarModels;

use App\Filament\Resources\CarModels\Pages\CreateCarModel;
use App\Filament\Resources\CarModels\Pages\EditCarModel;
use App\Filament\Resources\CarModels\Pages\ListCarModels;
use App\Filament\Resources\CarModels\RelationManagers\ModificationsRelationManager;
use App\Models\CarModel;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CarModelResource extends Resource
{
    protected static ?string $model = CarModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare3Stack3d;

    protected static string|\UnitEnum|null $navigationGroup = 'Автомобили';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'модель';

    protected static ?string $pluralModelLabel = 'модели';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('car_make_id')
                    ->label('Марка')
                    ->relationship('make', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->label('Модель')
                    ->required(),
                TextInput::make('year_from')
                    ->label('Год с')
                    ->numeric(),
                TextInput::make('year_to')
                    ->label('Год по')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn ($query) => $query->with('make')->withCount('modifications'))
            ->columns([
                TextColumn::make('make.name')
                    ->label('Марка')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Модель')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('years')
                    ->label('Годы')
                    ->state(fn (CarModel $record) => $record->year_from.' – '.($record->year_to ?? 'н.в.')),
                TextColumn::make('modifications_count')
                    ->label('Модификаций'),
            ])
            ->filters([
                SelectFilter::make('make')
                    ->label('Марка')
                    ->relationship('make', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ModificationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCarModels::route('/'),
            'create' => CreateCarModel::route('/create'),
            'edit' => EditCarModel::route('/{record}/edit'),
        ];
    }
}

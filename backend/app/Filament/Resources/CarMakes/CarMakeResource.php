<?php

namespace App\Filament\Resources\CarMakes;

use App\Filament\Resources\CarMakes\Pages\ManageCarMakes;
use App\Models\CarMake;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CarMakeResource extends Resource
{
    protected static ?string $model = CarMake::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'Автомобили';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'марка';

    protected static ?string $pluralModelLabel = 'марки';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required(),
                TextInput::make('slug')
                    ->label('Slug (URL)')
                    ->helperText('Заполнится автоматически')
                    ->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn ($query) => $query->withCount('models'))
            ->columns([
                TextColumn::make('name')
                    ->label('Марка')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('models_count')
                    ->label('Моделей'),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCarMakes::route('/'),
        ];
    }
}

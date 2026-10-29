<?php

namespace App\Filament\Resources\Suppliers;

use App\Enums\SupplierType;
use App\Filament\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Resources\Suppliers\Pages\ViewSupplier;
use App\Filament\Resources\Suppliers\RelationManagers\PriceImportsRelationManager;
use App\Models\Supplier;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Закупки';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'поставщик';

    protected static ?string $pluralModelLabel = 'поставщики';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        $columnLabels = [
            'brand' => 'Бренд',
            'article' => 'Артикул',
            'name' => 'Название',
            'price' => 'Цена',
            'quantity' => 'Остаток',
            'delivery_days' => 'Срок',
        ];

        return $schema
            ->components([
                Section::make('Поставщик')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required(),
                        TextInput::make('code')
                            ->label('Код')
                            ->helperText('Латиницей, используется в командах импорта')
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->required(),
                        Select::make('type')
                            ->label('Тип')
                            ->options(SupplierType::class)
                            ->live()
                            ->required(),
                        TextInput::make('delivery_days')
                            ->label('Доп. срок, дней')
                            ->helperText('Добавляется к сроку из прайса: доставка от поставщика до вас')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                    ]),
                Section::make('Формат прайса (CSV)')
                    ->columns(3)
                    ->visible(fn (Get $get) => $get('type') === SupplierType::PriceFile || $get('type') === SupplierType::PriceFile->value)
                    ->schema([
                        TextInput::make('settings.delimiter')
                            ->label('Разделитель')
                            ->default(';'),
                        Select::make('settings.encoding')
                            ->label('Кодировка')
                            ->options(['UTF-8' => 'UTF-8', 'Windows-1251' => 'Windows-1251'])
                            ->default('UTF-8'),
                        TextInput::make('settings.skip_rows')
                            ->label('Пропустить строк сверху')
                            ->numeric()
                            ->default(1),
                        ...collect($columnLabels)->map(fn ($label, $key) => TextInput::make("settings.columns.$key")
                            ->label("Колонка «{$label}»")
                            ->helperText('Номер с нуля')
                            ->numeric()
                            ->default(Supplier::DEFAULT_COLUMNS[$key]))->values()->all(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn ($query) => $query->withCount('offers'))
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('code')
                    ->label('Код')
                    ->color('gray'),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge(),
                TextColumn::make('offers_count')
                    ->label('Позиций')
                    ->numeric(),
                TextColumn::make('delivery_days')
                    ->label('Доп. срок')
                    ->suffix(' дн.'),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PriceImportsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'view' => ViewSupplier::route('/{record}'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }
}

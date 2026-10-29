<?php

namespace App\Filament\Resources\MarkupRules;

use App\Filament\Resources\MarkupRules\Pages\ManageMarkupRules;
use App\Models\MarkupRule;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MarkupRuleResource extends Resource
{
    protected static ?string $model = MarkupRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|\UnitEnum|null $navigationGroup = 'Закупки';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'правило наценки';

    protected static ?string $pluralModelLabel = 'наценки';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->columnSpanFull(),
                Select::make('supplier_id')
                    ->label('Поставщик')
                    ->relationship('supplier', 'name')
                    ->placeholder('Любой'),
                Select::make('brand_id')
                    ->label('Бренд')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Любой'),
                TextInput::make('price_from')
                    ->label('Закупка от')
                    ->numeric()
                    ->suffix('₽')
                    ->default(0)
                    ->required(),
                TextInput::make('price_to')
                    ->label('Закупка до (не включая)')
                    ->numeric()
                    ->suffix('₽')
                    ->placeholder('без ограничения'),
                TextInput::make('percent')
                    ->label('Наценка')
                    ->numeric()
                    ->suffix('%')
                    ->required(),
                TextInput::make('priority')
                    ->label('Приоритет')
                    ->helperText('Правила проверяются от большего приоритета к меньшему, срабатывает первое подходящее')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Активно')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                TextColumn::make('supplier.name')
                    ->label('Поставщик')
                    ->placeholder('Любой'),
                TextColumn::make('brand.name')
                    ->label('Бренд')
                    ->placeholder('Любой'),
                TextColumn::make('price_from')
                    ->label('От')
                    ->money('RUB'),
                TextColumn::make('price_to')
                    ->label('До')
                    ->money('RUB')
                    ->placeholder('∞'),
                TextColumn::make('percent')
                    ->label('Наценка')
                    ->suffix('%')
                    ->weight('bold'),
                TextColumn::make('priority')
                    ->label('Приоритет')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean(),
            ])
            ->defaultSort('priority', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMarkupRules::route('/'),
        ];
    }
}

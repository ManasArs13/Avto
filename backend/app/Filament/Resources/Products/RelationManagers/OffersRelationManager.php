<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Offer;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OffersRelationManager extends RelationManager
{
    protected static string $relationship = 'offers';

    protected static ?string $title = 'Предложения поставщиков';

    protected static ?string $modelLabel = 'предложение';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('supplier_id')
                    ->label('Поставщик')
                    ->relationship('supplier', 'name')
                    ->required(),
                TextInput::make('purchase_price')
                    ->label('Закупочная цена')
                    ->required()
                    ->numeric()
                    ->suffix('₽'),
                TextInput::make('quantity')
                    ->label('Остаток')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('delivery_days')
                    ->label('Срок, дней')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('multiplicity')
                    ->label('Кратность')
                    ->required()
                    ->numeric()
                    ->default(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('supplier', 'product'))
            ->columns([
                TextColumn::make('supplier.name')
                    ->label('Поставщик'),
                TextColumn::make('purchase_price')
                    ->label('Закупка')
                    ->money('RUB')
                    ->sortable(),
                TextColumn::make('sale_price')
                    ->label('Продажа')
                    ->state(fn (Offer $record) => $record->salePrice())
                    ->money('RUB')
                    ->weight('bold'),
                TextColumn::make('quantity')
                    ->label('Остаток')
                    ->sortable(),
                TextColumn::make('delivery_days')
                    ->label('Срок')
                    ->state(fn (Offer $record) => $record->totalDeliveryDays())
                    ->suffix(' дн.'),
                TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->since(),
            ])
            ->defaultSort('purchase_price')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}

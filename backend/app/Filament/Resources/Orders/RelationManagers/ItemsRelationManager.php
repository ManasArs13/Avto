<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Enums\OrderItemStatus;
use App\Models\OrderItem;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Позиции';

    protected static ?string $modelLabel = 'позиция';

    // Позиции видны и на странице просмотра заказа.
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->label('Статус позиции')
                    ->options(OrderItemStatus::class)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('supplier'))
            ->columns([
                TextColumn::make('brand_name')
                    ->label('Бренд'),
                TextColumn::make('article')
                    ->label('Артикул')
                    ->weight('bold')
                    ->copyable(),
                TextColumn::make('name')
                    ->label('Название')
                    ->limit(40),
                TextColumn::make('supplier.name')
                    ->label('Поставщик'),
                TextColumn::make('quantity')
                    ->label('Кол-во'),
                TextColumn::make('purchase_price')
                    ->label('Закупка')
                    ->money('RUB'),
                TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB'),
                TextColumn::make('margin')
                    ->label('Маржа')
                    ->state(fn (OrderItem $record) => ($record->price - $record->purchase_price) * $record->quantity)
                    ->money('RUB')
                    ->color('success'),
                TextColumn::make('delivery_days')
                    ->label('Срок')
                    ->suffix(' дн.'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),
            ])
            ->recordActions([
                EditAction::make()->label('Статус'),
            ])
            ->toolbarActions([
                BulkAction::make('markOrdered')
                    ->label('Отметить как заказанные')
                    ->icon('heroicon-o-truck')
                    ->action(fn (Collection $records) => $records->each->update(['status' => OrderItemStatus::Ordered])),
            ]);
    }
}

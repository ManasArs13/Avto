<?php

namespace App\Filament\Resources\Orders;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\ItemsRelationManager;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|\UnitEnum|null $navigationGroup = 'Продажи';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'заказ';

    protected static ?string $pluralModelLabel = 'заказы';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationBadge(): ?string
    {
        $count = Order::where('status', OrderStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Статус')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Статус заказа')
                            ->options(OrderStatus::class)
                            ->required(),
                        Select::make('payment_status')
                            ->label('Оплата')
                            ->options(PaymentStatus::class)
                            ->required(),
                    ]),
                Section::make('Покупатель')
                    ->columns(2)
                    ->schema([
                        TextInput::make('contact_name')
                            ->label('Имя')
                            ->required(),
                        TextInput::make('contact_phone')
                            ->label('Телефон')
                            ->tel()
                            ->required(),
                        TextInput::make('contact_email')
                            ->label('Email')
                            ->email(),
                    ]),
                Section::make('Доставка и оплата')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('delivery_method')
                            ->label('Способ доставки')
                            ->options(DeliveryMethod::class)
                            ->required(),
                        TextInput::make('delivery_cost')
                            ->label('Стоимость доставки')
                            ->numeric()
                            ->suffix('₽'),
                        TextInput::make('delivery_address')
                            ->label('Адрес')
                            ->columnSpanFull(),
                        Select::make('payment_method')
                            ->label('Способ оплаты')
                            ->options(PaymentMethod::class)
                            ->required(),
                        Textarea::make('comment')
                            ->label('Комментарий')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Заказ')
                    ->schema([
                        TextEntry::make('number')->label('Номер')->weight('bold')->copyable(),
                        TextEntry::make('created_at')->label('Создан')->dateTime('d.m.Y H:i'),
                        TextEntry::make('status')->label('Статус')->badge(),
                        TextEntry::make('payment_status')->label('Оплата')->badge(),
                    ]),
                Section::make('Покупатель')
                    ->schema([
                        TextEntry::make('contact_name')->label('Имя'),
                        TextEntry::make('contact_phone')->label('Телефон')->copyable(),
                        TextEntry::make('contact_email')->label('Email')->placeholder('—'),
                        TextEntry::make('customer.email')->label('Аккаунт')->placeholder('Гость'),
                    ]),
                Section::make('Доставка и оплата')
                    ->schema([
                        TextEntry::make('delivery_method')->label('Доставка'),
                        TextEntry::make('delivery_address')->label('Адрес')->placeholder('—'),
                        TextEntry::make('payment_method')->label('Оплата'),
                        TextEntry::make('subtotal')->label('Товары')->money('RUB'),
                        TextEntry::make('delivery_cost')->label('Доставка')->money('RUB'),
                        TextEntry::make('total')->label('Итого')->money('RUB')->weight('bold'),
                    ]),
                TextEntry::make('comment')
                    ->label('Комментарий')
                    ->placeholder('—')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->columns([
                TextColumn::make('number')
                    ->label('Номер')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('contact_name')
                    ->label('Покупатель')
                    ->description(fn (Order $record) => $record->contact_phone)
                    ->searchable(['contact_name', 'contact_phone', 'contact_email']),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),
                TextColumn::make('payment_status')
                    ->label('Оплата')
                    ->badge(),
                TextColumn::make('delivery_method')
                    ->label('Доставка')
                    ->toggleable(),
                TextColumn::make('total')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(OrderStatus::class)
                    ->multiple(),
                SelectFilter::make('payment_status')
                    ->label('Оплата')
                    ->options(PaymentStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        // Заказы создаются с сайта и из приложения. TODO: оформление заказа по телефону из админки.
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}

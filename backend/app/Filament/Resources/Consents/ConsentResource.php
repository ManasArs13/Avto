<?php

namespace App\Filament\Resources\Consents;

use App\Enums\ConsentType;
use App\Filament\Resources\Consents\Pages\ListConsents;
use App\Models\Consent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Журнал согласий — доказательство для Роскомнадзора: кто, когда, на какую редакцию документа
 * дал или отозвал согласие. Только чтение.
 */
class ConsentResource extends Resource
{
    protected static ?string $model = Consent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'согласие';

    protected static ?string $pluralModelLabel = 'журнал согласий';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('customer', 'order'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Когда')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Согласие'),
                IconColumn::make('granted')
                    ->label('Дано')
                    ->boolean(),
                TextColumn::make('subject')
                    ->label('Кто')
                    ->state(fn (Consent $record) => match (true) {
                        $record->customer !== null => "{$record->customer->name} <{$record->customer->email}>",
                        $record->order !== null => "Гость, заказ № {$record->order->number}",
                        default => 'Аккаунт удалён',
                    }),
                TextColumn::make('document_version')
                    ->label('Редакция')
                    ->formatStateUsing(fn (Consent $record) => "{$record->document_slug} v{$record->document_version}"),
                TextColumn::make('ip')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user_agent')
                    ->label('Браузер')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Согласие')
                    ->options(ConsentType::class),
                TernaryFilter::make('granted')
                    ->label('Дано / отозвано'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsents::route('/'),
        ];
    }
}

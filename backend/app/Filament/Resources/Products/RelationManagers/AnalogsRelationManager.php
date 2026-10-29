<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Concerns\ReadOnlyForViewers;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnalogsRelationManager extends RelationManager
{
    use ReadOnlyForViewers;

    protected static string $relationship = 'analogs';

    protected static ?string $title = 'Аналоги (кроссы)';

    protected static ?string $modelLabel = 'аналог';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (Product $record) => "{$record->brand->name} {$record->article} — {$record->name}")
            ->modifyQueryUsing(fn (Builder $query) => $query->with('brand'))
            ->columns([
                TextColumn::make('brand.name')
                    ->label('Бренд'),
                TextColumn::make('article')
                    ->label('Артикул')
                    ->weight('bold'),
                TextColumn::make('name')
                    ->label('Название'),
                TextColumn::make('source')
                    ->label('Источник')
                    ->state(fn (Product $record) => $record->pivot->source)
                    ->badge(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Добавить аналог')
                    ->recordSelectSearchColumns(['article', 'article_normalized', 'name'])
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->with('brand'))
                    ->preloadRecordSelect(false)
                    // Связь должна работать в обе стороны.
                    ->after(fn (array $data) => Product::find($data['recordId'])?->addAnalog($this->getOwnerRecord())),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Открыть')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Product $record) => ProductResource::getUrl('edit', ['record' => $record])),
                DetachAction::make()
                    ->label('Убрать')
                    ->after(fn (Product $record) => $record->analogs()->detach($this->getOwnerRecord())),
            ]);
    }
}

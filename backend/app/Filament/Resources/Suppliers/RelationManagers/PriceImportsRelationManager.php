<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Enums\ImportStatus;
use App\Filament\Concerns\ReadOnlyForViewers;
use App\Jobs\ImportPriceFile;
use App\Models\PriceImport;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PriceImportsRelationManager extends RelationManager
{
    use ReadOnlyForViewers;

    protected static string $relationship = 'priceImports';

    protected static ?string $title = 'Загрузки прайсов';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Загружен')
                    ->dateTime('d.m.Y H:i'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge(),
                TextColumn::make('rows_total')
                    ->label('Строк')
                    ->numeric(),
                TextColumn::make('rows_imported')
                    ->label('Загружено')
                    ->numeric()
                    ->color('success'),
                TextColumn::make('rows_failed')
                    ->label('Ошибок')
                    ->numeric()
                    ->color(fn (int $state) => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('duration')
                    ->label('Время')
                    ->state(fn (PriceImport $record) => $record->finished_at && $record->started_at
                        ? $record->started_at->diffForHumans($record->finished_at, true)
                        : null)
                    ->placeholder('—'),
                TextColumn::make('error')
                    ->label('Ошибка')
                    ->limit(60)
                    ->tooltip(fn (PriceImport $record) => $record->error)
                    ->placeholder('—')
                    ->color('danger'),
            ])
            ->defaultSort('id', 'desc')
            // Импорт идёт в фоне — обновляем таблицу, пока он не закончится.
            ->poll('5s')
            ->headerActions([
                Action::make('upload')
                    ->label('Загрузить прайс')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->hidden(fn () => $this->isReadOnly())
                    ->schema([
                        FileUpload::make('file')
                            ->label('CSV-файл')
                            ->disk('local')
                            ->directory(fn () => 'prices/'.$this->getOwnerRecord()->code)
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                            ->maxSize(200 * 1024)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $import = $this->getOwnerRecord()->priceImports()->create([
                            'file_path' => $data['file'],
                            'status' => ImportStatus::Pending,
                        ]);
                        ImportPriceFile::dispatch($import);

                        Notification::make()
                            ->title('Прайс поставлен в очередь на загрузку')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}

<?php

namespace App\Filament\Resources\LegalDocuments;

use App\Filament\Resources\LegalDocuments\Pages\EditLegalDocument;
use App\Filament\Resources\LegalDocuments\Pages\ListLegalDocuments;
use App\Filament\Resources\LegalDocuments\Pages\ViewLegalDocument;
use App\Models\LegalDocument;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LegalDocumentResource extends Resource
{
    protected static ?string $model = LegalDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|\UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'документ';

    protected static ?string $pluralModelLabel = 'документы для покупателей';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        $variables = collect(Setting::SHOP_FIELDS)->map(fn ($label, $key) => "{{{$key}}} — {$label}")->implode(', ');

        return $schema
            ->columns(1)
            ->components([
                TextInput::make('title')
                    ->label('Заголовок')
                    ->required(),
                MarkdownEditor::make('body')
                    ->label('Текст')
                    ->helperText("Реквизиты подставляются автоматически: {$variables}. После сохранения изменённого текста номер редакции увеличится — новые согласия будут даваться уже на неё.")
                    ->toolbarButtons([['heading', 'bold', 'italic', 'link'], ['bulletList', 'orderedList'], ['undo', 'redo']])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Документ')
                    ->weight('bold')
                    ->description(fn (LegalDocument $record) => "/legal/{$record->slug}"),
                TextColumn::make('version')
                    ->label('Редакция'),
                TextColumn::make('updated_at')
                    ->label('Изменён')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->defaultSort('sort')
            ->paginated(false)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLegalDocuments::route('/'),
            'view' => ViewLegalDocument::route('/{record}'),
            'edit' => EditLegalDocument::route('/{record}/edit'),
        ];
    }
}

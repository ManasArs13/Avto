<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|\UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'категория';

    protected static ?string $pluralModelLabel = 'категории';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название')
                    ->required(),
                Select::make('parent_id')
                    ->label('Родительская категория')
                    ->relationship('parent', 'name', fn ($query, ?Category $record) => $query->when($record, fn ($q) => $q->whereKeyNot($record->id))),
                TextInput::make('slug')
                    ->label('Slug (URL)')
                    ->helperText('Заполнится автоматически')
                    ->unique(ignoreRecord: true),
                TextInput::make('sort')
                    ->label('Сортировка')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Показывать')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn ($query) => $query->with('parent')->withCount('products'))
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->description(fn (Category $record) => $record->parent?->name)
                    ->searchable(),
                TextColumn::make('products_count')
                    ->label('Товаров')
                    ->numeric(),
                TextColumn::make('sort')
                    ->label('Сорт.')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Показывать')
                    ->boolean(),
            ])
            ->defaultSort('sort')
            ->reorderable('sort')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\AnalogsRelationManager;
use App\Filament\Resources\Products\RelationManagers\OffersRelationManager;
use App\Models\Product;
use App\Support\Article;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|\UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'товар';

    protected static ?string $pluralModelLabel = 'товары';

    protected static ?string $recordTitleAttribute = 'article';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основное')
                    ->columns(2)
                    ->schema([
                        Select::make('brand_id')
                            ->label('Бренд')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('article')
                            ->label('Артикул')
                            ->helperText('Как у производителя. Для поиска нормализуется автоматически.')
                            ->required(),
                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->columnSpanFull(),
                        Select::make('category_id')
                            ->label('Категория')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('weight')
                            ->label('Вес, кг')
                            ->numeric(),
                        Textarea::make('description')
                            ->label('Описание')
                            ->rows(4)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Показывать на сайте')
                            ->default(true),
                    ]),
                Section::make('Характеристики')
                    ->collapsed()
                    ->schema([
                        KeyValue::make('specs')
                            ->hiddenLabel()
                            ->keyLabel('Параметр')
                            ->valueLabel('Значение'),
                    ]),
                Section::make('Применимость')
                    ->collapsed()
                    ->schema([
                        Select::make('modifications')
                            ->hiddenLabel()
                            ->relationship('modifications', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->model->make->name.' '.$record->model->name.' '.$record->name)
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('article')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('brand', 'category')->withCount('offers'))
            ->columns([
                TextColumn::make('brand.name')
                    ->label('Бренд')
                    ->sortable(),
                TextColumn::make('article')
                    ->label('Артикул')
                    ->copyable()
                    ->weight('bold')
                    // Ищем по нормализованному артикулу, чтобы "04465-33450" и "0446533450" находили одно и то же.
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->where('article_normalized', 'like', Article::normalize($search).'%')
                        ->orWhere('name', 'ilike', "%{$search}%")),
                TextColumn::make('name')
                    ->label('Название')
                    ->limit(50),
                TextColumn::make('category.name')
                    ->label('Категория')
                    ->toggleable(),
                TextColumn::make('offers_count')
                    ->label('Предложений')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'gray'),
                IconColumn::make('is_active')
                    ->label('На сайте')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('brand')
                    ->label('Бренд')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('category')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('На сайте'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            // Таблица может вырасти до миллионов строк: без COUNT(*) по всей таблице.
            ->paginationMode(PaginationMode::Simple);
    }

    public static function getRelations(): array
    {
        return [
            OffersRelationManager::class,
            AnalogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}

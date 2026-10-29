<?php

namespace App\Filament\Resources\Brands;

use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Models\Brand;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'бренд';

    protected static ?string $pluralModelLabel = 'бренды';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Бренд')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required(),
                        TextInput::make('slug')
                            ->label('Slug (URL)')
                            ->helperText('Заполнится автоматически')
                            ->unique(ignoreRecord: true),
                        TextInput::make('country')
                            ->label('Страна'),
                        Toggle::make('is_oem')
                            ->label('Оригинал (автопроизводитель)')
                            ->inline(false),
                    ]),
                Section::make('Алиасы')
                    ->description('Как бренд пишут в прайсах поставщиков. При загрузке прайса они сопоставляются с этим брендом.')
                    ->schema([
                        Repeater::make('aliases')
                            ->hiddenLabel()
                            ->relationship()
                            ->simple(TextInput::make('alias')->required())
                            ->addActionLabel('Добавить алиас'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn ($query) => $query->withCount('products'))
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('country')
                    ->label('Страна'),
                IconColumn::make('is_oem')
                    ->label('Оригинал')
                    ->boolean(),
                TextColumn::make('products_count')
                    ->label('Товаров')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('is_oem')->label('Оригинал'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBrands::route('/'),
            'create' => CreateBrand::route('/create'),
            'edit' => EditBrand::route('/{record}/edit'),
        ];
    }
}

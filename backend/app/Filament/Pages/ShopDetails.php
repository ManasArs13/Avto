<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Реквизиты продавца. Показываются в подвале сайта и подставляются в документы ({{legal_name}} и т.д.).
 * Закон требует сообщать покупателю наименование, ОГРН, адрес и контакты продавца (ЗоЗПП ст. 26.1).
 */
class ShopDetails extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|\UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?string $navigationLabel = 'Реквизиты магазина';

    protected static ?string $title = 'Реквизиты магазина';

    protected static ?int $navigationSort = 1;

    /** @var array<string, ?string> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) Filament::auth()->user()?->role->canManageStaff();
    }

    public function mount(): void
    {
        $this->form->fill(Setting::shop());
    }

    public function form(Schema $schema): Schema
    {
        $field = fn (string $key) => TextInput::make($key)->label(Setting::SHOP_FIELDS[$key]);

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Продавец')
                    ->description('Как в выписке ЕГРИП / ЕГРЮЛ.')
                    ->columns(2)
                    ->schema([
                        $field('legal_name')->placeholder('ИП Иванов Иван Иванович')->required()->columnSpanFull(),
                        $field('inn')->regex('/^\d{10}(\d{2})?$/')->validationMessages(['regex' => 'ИНН — 10 цифр (ООО) или 12 (ИП).'])->required(),
                        $field('ogrn')->regex('/^\d{13}(\d{2})?$/')->validationMessages(['regex' => 'ОГРН — 13 цифр, ОГРНИП — 15.'])->required(),
                        $field('legal_address')->required()->columnSpanFull(),
                    ]),
                Section::make('Магазин и контакты')
                    ->columns(2)
                    ->schema([
                        $field('shop_name')->required(),
                        $field('site_url')->url()->placeholder('https://avto.ru'),
                        $field('store_address')->columnSpanFull(),
                        $field('phone')->tel()->required(),
                        $field('email')->email()->required()->helperText('Сюда же приходят обращения по персональным данным.'),
                        $field('work_hours')->placeholder('Пн–Пт 9:00–19:00, Сб 10:00–16:00'),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Сохранить')->submit('save')->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        Setting::saveShop($this->form->getState());

        Notification::make()->title('Реквизиты сохранены')->success()->send();
    }
}

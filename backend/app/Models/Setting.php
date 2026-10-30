<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Реквизиты и контакты магазина (ключ → значение).
 * Состав полей — в SHOP_FIELDS; выводятся на сайте и подставляются в документы как {{ключ}}.
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    /** Что обязан сообщить продавец при дистанционной продаже (ЗоЗПП ст. 26.1, правила продажи № 2463). */
    public const SHOP_FIELDS = [
        'shop_name' => 'Название магазина',
        'legal_name' => 'Продавец (ИП ФИО или ООО «…»)',
        'inn' => 'ИНН',
        'ogrn' => 'ОГРН / ОГРНИП',
        'legal_address' => 'Юридический адрес',
        'store_address' => 'Адрес магазина / пункта выдачи',
        'phone' => 'Телефон',
        'email' => 'Email',
        'work_hours' => 'Часы работы',
        'site_url' => 'Адрес сайта',
    ];

    private const CACHE_KEY = 'settings.shop';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
    }

    /** @return array<string, string> все поля SHOP_FIELDS, незаполненные — пустой строкой */
    public static function shop(): array
    {
        $values = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());

        return collect(self::SHOP_FIELDS)->map(fn ($label, string $key) => (string) ($values[$key] ?? ''))->all();
    }

    /** @param  array<string, ?string>  $values */
    public static function saveShop(array $values): void
    {
        foreach (array_intersect_key($values, self::SHOP_FIELDS) as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}

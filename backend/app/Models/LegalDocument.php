<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Документ для покупателей: политика обработки ПДн, согласия, оферта, доставка, возврат.
 * Каждое изменение текста — новая редакция (version): в журнале согласий видно, на какую соглашался покупатель.
 */
#[Fillable(['slug', 'title', 'body', 'sort'])]
class LegalDocument extends Model
{
    protected static function booted(): void
    {
        static::updating(function (LegalDocument $document) {
            if ($document->isDirty('body')) {
                $document->version++;
            }
        });
    }

    public static function currentVersion(string $slug): ?int
    {
        return static::where('slug', $slug)->value('version');
    }

    /** Текст для сайта: подставляем реквизиты и превращаем Markdown в HTML без «сырого» HTML. */
    public function html(): string
    {
        $shop = Setting::shop();
        $body = preg_replace_callback(
            '/\{\{\s*(\w+)\s*\}\}/',
            fn (array $m) => array_key_exists($m[1], $shop) ? ($shop[$m[1]] ?: '[не заполнено: '.Setting::SHOP_FIELDS[$m[1]].']') : $m[0],
            $this->body,
        );

        return Str::markdown($body, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }
}

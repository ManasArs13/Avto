<?php

namespace App\Support;

use Illuminate\Support\Str;

class Article
{
    // Кириллица, которую путают с латиницей при наборе артикула.
    private const LOOKALIKES = [
        'А' => 'A', 'В' => 'B', 'Е' => 'E', 'К' => 'K', 'М' => 'M', 'Н' => 'H',
        'О' => 'O', 'Р' => 'P', 'С' => 'C', 'Т' => 'T', 'У' => 'Y', 'Х' => 'X',
    ];

    /**
     * "04465-33450", "0446533450", "04465 33450" -> "0446533450".
     */
    public static function normalize(?string $article): string
    {
        $article = mb_strtoupper(trim((string) $article));
        $article = strtr($article, self::LOOKALIKES);

        return preg_replace('/[^A-Z0-9]/', '', $article);
    }

    /**
     * Общая часть каталожного номера ВАЗ без суффикса исполнения.
     *
     * У ВАЗ один и тот же номер пишут по-старому и по-новому:
     *   "2108-3501080"     (код модели 4 цифры)      -> "210803501080"
     *   "21080-3501080"    (5 цифр, без суффикса)    -> "210803501080"
     *   "21080-3501080-00" (с суффиксом исполнения)  -> "210803501080"
     * Для прочих артикулов возвращает null.
     */
    public static function vazStem(string $normalized): ?string
    {
        return match (true) {
            (bool) preg_match('/^\d{11}$/', $normalized) => substr($normalized, 0, 4).'0'.substr($normalized, 4),
            (bool) preg_match('/^\d{12}(\d{2})?$/', $normalized) => substr($normalized, 0, 12),
            default => null,
        };
    }

    /**
     * Бренды из прайсов: "Mann-Filter", "MANN FILTER" -> "MANNFILTER".
     */
    public static function normalizeBrand(?string $brand): string
    {
        $brand = mb_strtoupper(Str::ascii(trim((string) $brand)));

        return preg_replace('/[^A-Z0-9]/', '', $brand);
    }
}

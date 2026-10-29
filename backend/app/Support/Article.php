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
     * Бренды из прайсов: "Mann-Filter", "MANN FILTER" -> "MANNFILTER".
     */
    public static function normalizeBrand(?string $brand): string
    {
        $brand = mb_strtoupper(Str::ascii(trim((string) $brand)));

        return preg_replace('/[^A-Z0-9]/', '', $brand);
    }
}

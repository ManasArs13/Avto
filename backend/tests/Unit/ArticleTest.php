<?php

namespace Tests\Unit;

use App\Support\Article;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ArticleTest extends TestCase
{
    #[DataProvider('articles')]
    public function test_normalize(string $input, string $expected): void
    {
        $this->assertSame($expected, Article::normalize($input));
    }

    public static function articles(): array
    {
        return [
            'дефисы' => ['21080-1012005-08', '21080101200508'],
            'пробелы и слэш' => ['W 914/2', 'W9142'],
            'нижний регистр' => ['bcpr6es-11', 'BCPR6ES11'],
            'кириллица вместо латиницы' => ['ВР6ЕС', 'BP6EC'],
            'пробелы по краям' => ['  0 451 103 079 ', '0451103079'],
        ];
    }

    #[DataProvider('vazNumbers')]
    public function test_vaz_stem(string $input, ?string $expected): void
    {
        $this->assertSame($expected, Article::vazStem(Article::normalize($input)));
    }

    public static function vazNumbers(): array
    {
        return [
            'старый формат, 4 цифры модели' => ['2108-3501080', '210803501080'],
            'новый без суффикса' => ['21080-3501080', '210803501080'],
            'новый с суффиксом' => ['21080-3501080-00', '210803501080'],
            'другой суффикс исполнения' => ['21080-1012005-08', '210801012005'],
            'номер Renault/Vesta — не формат ВАЗ' => ['8450108101', null],
            'буквы — не ВАЗ' => ['W 914/2', null],
        ];
    }

    public function test_normalize_brand(): void
    {
        $this->assertSame('AVTOVAZ', Article::normalizeBrand('АвтоВАЗ'));
        $this->assertSame('VAZ', Article::normalizeBrand('ВАЗ'));
        $this->assertSame('MANNFILTER', Article::normalizeBrand('Mann-Filter'));
    }
}

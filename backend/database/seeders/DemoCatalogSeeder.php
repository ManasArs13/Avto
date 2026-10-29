<?php

namespace Database\Seeders;

use App\Enums\SupplierType;
use App\Models\Brand;
use App\Models\CarMake;
use App\Models\Category;
use App\Models\Customer;
use App\Models\MarkupRule;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Supplier;
use App\Support\Article;
use Illuminate\Database\Seeder;

/**
 * Демо-каталог оригинальных запчастей ВАЗ (LADA) для разработки.
 * Номера построены по схеме каталога ВАЗ, но кроссы, применимость и цены
 * условные — не используйте их как справочные данные.
 */
class DemoCatalogSeeder extends Seeder
{
    // Группы модификаций для применимости.
    private const FWD8 = ['granta-8v', 'kalina-8v', '2114-8v', '2110-8v'];

    private const FWD16 = ['granta-16v', 'priora-16v'];

    private const VESTA = ['vesta-16', 'vesta-18'];

    private const CLASSIC = ['niva-17', 'travel-17', '2107-16'];

    public function run(): void
    {
        $brands = $this->brands();
        $categories = $this->categories();
        $modifications = $this->vehicles();
        $suppliers = $this->suppliers();
        $this->markupRules();

        foreach ($this->catalog() as $item) {
            [$article, $name, $category, $applicability, $price, $specs, $analogs] = $item;
            $modIds = array_map(fn ($key) => $modifications[$key], $applicability);

            $original = $this->product($brands['LADA'], $article, $name, $categories[$category], $specs);
            $original->modifications()->syncWithoutDetaching($modIds);
            $this->offers($original, $suppliers, $price, withWarehouse: true);

            foreach ($analogs as [$analogBrand, $analogArticle, $analogName, $factor]) {
                $analog = $this->product($brands[$analogBrand], $analogArticle, $analogName, $categories[$category]);
                $analog->modifications()->syncWithoutDetaching($modIds);
                $original->addAnalog($analog, 'demo');
                $this->offers($analog, $suppliers, $price * $factor, withWarehouse: false);
            }
        }

        Customer::updateOrCreate(
            ['email' => 'customer@avto.local'],
            ['name' => 'Иван Петров', 'phone' => '+79990000000', 'password' => 'password', 'discount_percent' => 5],
        );
    }

    /**
     * [артикул LADA, название, категория, применимость, закупка ₽, характеристики,
     *  аналоги [бренд, артикул, название, цена относительно оригинала]]
     */
    private function catalog(): array
    {
        return [
            // Техобслуживание
            ['21080-1012005-08', 'Фильтр масляный', 'oil-filters', [...self::FWD8, ...self::FWD16, ...self::VESTA], 250,
                ['Резьба' => '3/4"-16 UNF', 'Высота, мм' => '76', 'Клапан' => 'противодренажный'], [
                    ['MANN-FILTER', 'W 914/2', 'Фильтр масляный', 1.3],
                    ['BOSCH', '0 451 103 079', 'Фильтр масляный', 1.1],
                    ['LUZAR', 'LFOM 0108', 'Фильтр масляный', 0.7],
                ]],
            ['21050-1012005-00', 'Фильтр масляный (классика, Нива)', 'oil-filters', self::CLASSIC, 230,
                ['Резьба' => '3/4"-16 UNF', 'Высота, мм' => '92'], [
                    ['MANN-FILTER', 'W 920/38', 'Фильтр масляный', 1.4],
                    ['FENOX', 'FO 2105', 'Фильтр масляный', 0.8],
                ]],
            ['8200768913', 'Фильтр масляный (Largus 8V)', 'oil-filters', ['largus-8v'], 450, [], [
                ['MANN-FILTER', 'W 75/3', 'Фильтр масляный', 1.2],
            ]],
            ['21120-1109080-00', 'Фильтр воздушный', 'air-filters', [...self::FWD8, ...self::FWD16], 350, [], [
                ['MANN-FILTER', 'C 2645', 'Фильтр воздушный', 1.3],
                ['LUZAR', 'LAF 1012', 'Фильтр воздушный', 0.7],
            ]],
            ['21129-1109080-00', 'Фильтр воздушный (Vesta)', 'air-filters', self::VESTA, 650, [], [
                ['LUZAR', 'LAF 0190', 'Фильтр воздушный', 0.7],
            ]],
            ['11180-8122020-00', 'Фильтр салонный', 'cabin-filters', ['granta-8v', 'granta-16v', 'kalina-8v', 'priora-16v'], 300, [], [
                ['MANN-FILTER', 'CU 1828', 'Фильтр салонный', 1.5],
                ['LUZAR', 'LAC 0190', 'Фильтр салонный', 0.8],
            ]],
            ['8450006898', 'Фильтр салонный (Vesta)', 'cabin-filters', self::VESTA, 700, [], [
                ['LUZAR', 'LAC 0198', 'Фильтр салонный угольный', 0.9],
            ]],
            ['21120-3707010-10', 'Свеча зажигания (16V)', 'spark-plugs', [...self::FWD16, ...self::VESTA], 180,
                ['Зазор, мм' => '1.0', 'Калильное число' => '17'], [
                    ['NGK', 'BCPR6ES-11', 'Свеча зажигания', 1.4],
                    ['DENSO', 'Q20PR-U11', 'Свеча зажигания', 1.5],
                ]],
            ['21080-3707010-00', 'Свеча зажигания (8V)', 'spark-plugs', self::FWD8, 120,
                ['Зазор, мм' => '0.7–0.8'], [
                    ['NGK', 'BPR6ES', 'Свеча зажигания', 1.6],
                    ['BOSCH', 'WR7DC', 'Свеча зажигания', 1.5],
                ]],

            // Двигатель
            ['21116-1006040-00', 'Ремень ГРМ (8V)', 'timing', self::FWD8, 900, ['Зубьев' => '113'], [
                ['GATES', '5670XS', 'Ремень ГРМ', 1.2],
            ]],
            ['21126-1006040-00', 'Ремень ГРМ (16V)', 'timing', [...self::FWD16, 'vesta-16'], 1600, ['Зубьев' => '137'], [
                ['GATES', '5656XS', 'Ремень ГРМ', 1.1],
            ]],
            ['21126-1006238-00', 'Ролик натяжной ГРМ (16V)', 'timing', [...self::FWD16, 'vesta-16'], 1300, [], [
                ['SKF', 'VKM 16620', 'Ролик натяжной', 1.3],
            ]],
            ['21080-1307010-01', 'Насос охлаждающей жидкости (помпа)', 'cooling', self::FWD8, 1500, [], [
                ['LUZAR', 'LWP 0108', 'Насос водяной', 0.7],
                ['FENOX', 'HB 1102', 'Насос водяной', 0.8],
            ]],
            ['21082-1306010-14', 'Термостат', 'cooling', [...self::FWD8, ...self::FWD16], 900, ['Открытие, °C' => '85'], [
                ['LUZAR', 'LT 0127', 'Термостат', 0.7],
            ]],

            // Тормоза
            ['21080-3501080-00', 'Колодки тормозные передние', 'brake-pads', [...self::FWD8, ...self::FWD16], 900, [], [
                ['FENOX', 'BP 43004', 'Колодки тормозные передние', 0.8],
                ['TRIALLI', 'PF 0108', 'Колодки тормозные передние', 0.9],
                ['BOSCH', '0 986 491 700', 'Колодки тормозные передние', 1.4],
            ]],
            ['8450108101', 'Колодки тормозные передние (Vesta)', 'brake-pads', self::VESTA, 2200, [], [
                ['FENOX', 'BP 43399', 'Колодки тормозные передние', 0.7],
                ['TRIALLI', 'PF 0190', 'Колодки тормозные передние', 0.8],
            ]],
            ['21210-3501090-00', 'Колодки тормозные передние (Нива)', 'brake-pads', ['niva-17', 'travel-17'], 1000, [], [
                ['FENOX', 'BP 43053', 'Колодки тормозные передние', 0.8],
            ]],
            ['21080-3501070-00', 'Диск тормозной передний R13', 'brake-discs', self::FWD8, 1300,
                ['Диаметр, мм' => '239', 'Толщина, мм' => '12'], [
                    ['FENOX', 'TB 2108', 'Диск тормозной', 0.8],
                ]],

            // Подвеска
            ['21900-2905402-00', 'Стойка амортизатора передняя', 'shock-absorbers', ['granta-8v', 'granta-16v', 'kalina-8v'], 3500, [], [
                ['SS20', 'SS10114', 'Стойка передняя «Стандарт»', 1.1],
                ['KAYABA', '3330013', 'Стойка передняя газомасляная', 1.6],
            ]],
            ['21080-3103020-00', 'Подшипник ступицы передний', 'wheel-bearings', [...self::FWD8, ...self::FWD16], 1100, [], [
                ['SKF', 'VKBA 1306', 'Подшипник ступицы', 1.5],
            ]],
            ['21080-2904192-00', 'Опора шаровая', 'ball-joints', [...self::FWD8, ...self::FWD16], 700, [], [
                ['TRIALLI', 'TS 0108', 'Опора шаровая', 0.9],
                ['FENOX', 'BJ 10001', 'Опора шаровая', 0.8],
            ]],

            // Электрика
            ['11180-3850010-00', 'Датчик кислорода', 'sensors', [...self::FWD8, ...self::FWD16], 3200, [], [
                ['BOSCH', '0 258 006 537', 'Лямбда-зонд', 1.2],
            ]],
            ['21120-3847010-03', 'Датчик положения коленвала', 'sensors', [...self::FWD8, ...self::FWD16, ...self::CLASSIC], 900, [], [
                ['BOSCH', '0 261 210 170', 'Датчик положения коленвала', 1.4],
            ]],
            ['21110-3705010-00', 'Модуль зажигания (8V)', 'ignition', self::FWD8, 2600, [], [
                ['BOSCH', '0 986 221 045', 'Катушка зажигания', 1.2],
            ]],
        ];
    }

    /** @return array<string, Brand> */
    private function brands(): array
    {
        $data = [
            // В прайсах оригинал ВАЗ пишут как угодно — все варианты ведут на один бренд.
            'LADA' => ['Россия', true, ['ВАЗ', 'АВТОВАЗ', 'AVTOVAZ', 'VAZ', 'LADA ORIGINAL', 'ВАЗ ОРИГИНАЛ', 'АВТОВАЗ (LADA)']],
            'MANN-FILTER' => ['Германия', false, ['MANN']],
            'BOSCH' => ['Германия', false, []],
            'NGK' => ['Япония', false, []],
            'DENSO' => ['Япония', false, []],
            'FENOX' => ['Беларусь', false, []],
            'LUZAR' => ['Россия', false, []],
            'TRIALLI' => ['Россия', false, []],
            'GATES' => ['США', false, []],
            'SKF' => ['Швеция', false, []],
            'SS20' => ['Россия', false, []],
            'KAYABA' => ['Япония', false, ['KYB']],
        ];

        $brands = [];
        foreach ($data as $name => [$country, $isOem, $aliases]) {
            $brand = Brand::firstOrCreate(['name' => $name], ['country' => $country, 'is_oem' => $isOem]);
            foreach ($aliases as $alias) {
                $brand->aliases()->firstOrCreate(['alias' => Article::normalizeBrand($alias)]);
            }
            $brands[$name] = $brand;
        }

        return $brands;
    }

    /** @return array<string, Category> */
    private function categories(): array
    {
        $tree = [
            'Техобслуживание' => [
                'oil-filters' => 'Масляные фильтры',
                'air-filters' => 'Воздушные фильтры',
                'cabin-filters' => 'Салонные фильтры',
                'spark-plugs' => 'Свечи зажигания',
            ],
            'Двигатель' => [
                'timing' => 'ГРМ: ремни и ролики',
                'cooling' => 'Охлаждение: помпы и термостаты',
            ],
            'Тормозная система' => [
                'brake-pads' => 'Тормозные колодки',
                'brake-discs' => 'Тормозные диски',
            ],
            'Подвеска' => [
                'shock-absorbers' => 'Стойки и амортизаторы',
                'wheel-bearings' => 'Ступичные подшипники',
                'ball-joints' => 'Шаровые опоры',
            ],
            'Электрика' => [
                'sensors' => 'Датчики',
                'ignition' => 'Зажигание',
            ],
        ];

        $categories = [];
        $sort = 0;
        foreach ($tree as $parentName => $children) {
            $parent = Category::firstOrCreate(['name' => $parentName], ['sort' => $sort++]);
            $childSort = 0;
            foreach ($children as $slug => $name) {
                $categories[$slug] = Category::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'parent_id' => $parent->id, 'sort' => $childSort++],
                );
            }
        }

        return $categories;
    }

    /** @return array<string, int> */
    private function vehicles(): array
    {
        // [модель => [год с, год по, [ключ => [название, двигатель, объём, л.с.]]]]
        $models = [
            'Vesta' => [2015, null, [
                'vesta-16' => ['1.6 16V (106 л.с.)', '21129', 1.6, 106],
                'vesta-18' => ['1.8 16V (122 л.с.)', '21179', 1.8, 122],
            ]],
            'Granta' => [2011, null, [
                'granta-8v' => ['1.6 8V (90 л.с.)', '11186', 1.6, 90],
                'granta-16v' => ['1.6 16V (106 л.с.)', '21127', 1.6, 106],
            ]],
            'Largus' => [2012, null, [
                'largus-8v' => ['1.6 8V (90 л.с.)', '11182', 1.6, 90],
            ]],
            'Niva Legend (2121, 4x4)' => [1977, null, [
                'niva-17' => ['1.7 (83 л.с.)', '21214', 1.7, 83],
            ]],
            'Niva Travel' => [2020, null, [
                'travel-17' => ['1.7 (80 л.с.)', '21214', 1.7, 80],
            ]],
            'Priora' => [2007, 2018, [
                'priora-16v' => ['1.6 16V (98 л.с.)', '21126', 1.6, 98],
            ]],
            'Kalina' => [2004, 2018, [
                'kalina-8v' => ['1.6 8V (81 л.с.)', '11183', 1.6, 81],
            ]],
            '2114 Samara' => [2001, 2013, [
                '2114-8v' => ['1.6 8V (81 л.с.)', '11183', 1.6, 81],
            ]],
            '2110' => [1995, 2007, [
                '2110-8v' => ['1.5 8V (77 л.с.)', '2111', 1.5, 77],
            ]],
            '2107' => [1982, 2012, [
                '2107-16' => ['1.6 (75 л.с.)', '21067', 1.6, 75],
            ]],
        ];

        $make = CarMake::firstOrCreate(['name' => 'LADA'], ['slug' => 'lada']);

        $ids = [];
        foreach ($models as $modelName => [$from, $to, $mods]) {
            $model = $make->models()->firstOrCreate(['name' => $modelName], ['year_from' => $from, 'year_to' => $to]);
            foreach ($mods as $key => [$name, $engine, $volume, $hp]) {
                $ids[$key] = $model->modifications()->firstOrCreate(['name' => $name], [
                    'engine_code' => $engine, 'engine_volume' => $volume, 'power_hp' => $hp,
                    'fuel' => 'Бензин', 'year_from' => $from, 'year_to' => $to,
                ])->id;
            }
        }

        return $ids;
    }

    /** @return array<string, Supplier> */
    private function suppliers(): array
    {
        return [
            'warehouse' => Supplier::firstOrCreate(['code' => 'warehouse'], [
                'name' => 'Склад магазина', 'type' => SupplierType::Warehouse, 'delivery_days' => 0,
            ]),
            'price' => Supplier::firstOrCreate(['code' => 'supplier-a'], [
                'name' => 'Поставщик А (прайс)', 'type' => SupplierType::PriceFile, 'delivery_days' => 1,
                'settings' => ['delimiter' => ';', 'encoding' => 'UTF-8', 'skip_rows' => 1, 'columns' => Supplier::DEFAULT_COLUMNS],
            ]),
            'api' => Supplier::firstOrCreate(['code' => 'supplier-b'], [
                'name' => 'Поставщик Б (API)', 'type' => SupplierType::Api, 'delivery_days' => 2,
            ]),
        ];
    }

    private function markupRules(): void
    {
        $ranges = [[0, 500, 60], [500, 2000, 40], [2000, 10000, 30], [10000, null, 20]];

        foreach ($ranges as [$from, $to, $percent]) {
            MarkupRule::firstOrCreate(
                ['supplier_id' => null, 'brand_id' => null, 'price_from' => $from],
                ['name' => 'Базовая '.$from.'–'.($to ?? '∞').' ₽', 'price_to' => $to, 'percent' => $percent],
            );
        }
    }

    private function product(Brand $brand, string $article, string $name, Category $category, array $specs = []): Product
    {
        return Product::firstOrCreate(
            ['brand_id' => $brand->id, 'article_normalized' => Article::normalize($article)],
            ['article' => $article, 'name' => $name, 'category_id' => $category->id, 'specs' => $specs ?: null],
        );
    }

    /**
     * Оригинал всегда есть на своём складе; у поставщиков цены гуляют ±15%.
     *
     * @param  array<string, Supplier>  $suppliers
     */
    private function offers(Product $product, array $suppliers, float $base, bool $withWarehouse): void
    {
        foreach ($suppliers as $key => $supplier) {
            if ($key === 'warehouse' && ! $withWarehouse) {
                continue;
            }

            Offer::updateOrCreate(
                ['supplier_id' => $supplier->id, 'product_id' => $product->id],
                [
                    'purchase_price' => round($base * (0.9 + random_int(0, 25) / 100), 2),
                    'quantity' => random_int(2, 40),
                    'delivery_days' => $key === 'warehouse' ? 0 : random_int(1, 5),
                ],
            );
        }
    }
}

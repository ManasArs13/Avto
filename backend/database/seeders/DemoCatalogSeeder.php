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
 * Демо-каталог для разработки. Артикулы похожи на настоящие,
 * но кроссы и применимость условные — не используйте их как справочные данные.
 */
class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $brands = $this->brands();
        $categories = $this->categories();
        $modifications = $this->vehicles();
        $suppliers = $this->suppliers();
        $this->markupRules();

        // [бренд, артикул, название, категория, применимость, аналоги [бренд, артикул, название]]
        $catalog = [
            ['TOYOTA', '04465-33450', 'Колодки тормозные передние', 'brake-pads', ['camry-25', 'camry-35', 'rav4-25'], [
                ['BREMBO', 'P 83 117', 'Колодки тормозные дисковые передние'],
                ['TRW', 'GDB3428', 'Колодки тормозные дисковые передние'],
                ['BOSCH', '0 986 494 360', 'Колодки тормозные передние'],
            ]],
            ['TOYOTA', '90915-YZZD4', 'Фильтр масляный', 'oil-filters', ['camry-25', 'rav4-20', 'rav4-25'], [
                ['MANN-FILTER', 'W 68/3', 'Фильтр масляный'],
                ['BOSCH', '0 451 103 316', 'Фильтр масляный'],
                ['FEBI', '32102', 'Фильтр масляный'],
            ]],
            ['TOYOTA', '17801-0V020', 'Фильтр воздушный', 'air-filters', ['camry-25', 'rav4-25'], [
                ['MANN-FILTER', 'C 26 003', 'Фильтр воздушный'],
                ['BOSCH', 'F 026 400 533', 'Фильтр воздушный'],
            ]],
            ['TOYOTA', '87139-0N010', 'Фильтр салонный', 'cabin-filters', ['camry-25', 'camry-35', 'rav4-20', 'rav4-25'], [
                ['MANN-FILTER', 'CU 1919', 'Фильтр салонный'],
                ['DENSO', 'DCF367P', 'Фильтр салонный угольный'],
            ]],
            ['TOYOTA', '90919-01253', 'Свеча зажигания', 'spark-plugs', ['camry-25', 'rav4-25'], [
                ['DENSO', 'FK20HR11', 'Свеча зажигания иридиевая'],
                ['NGK', 'ILKAR7B11', 'Свеча зажигания Laser Iridium'],
            ]],
            ['TOYOTA', '43512-33130', 'Диск тормозной передний', 'brake-discs', ['camry-25', 'camry-35'], [
                ['BREMBO', '09.A417.11', 'Диск тормозной вентилируемый'],
                ['TRW', 'DF4880', 'Диск тормозной'],
            ]],
            ['HYUNDAI/KIA', '26300-35505', 'Фильтр масляный', 'oil-filters', ['solaris-16', 'rio-16'], [
                ['MANN-FILTER', 'W 811/80', 'Фильтр масляный'],
                ['BOSCH', 'F 026 407 209', 'Фильтр масляный'],
            ]],
            ['HYUNDAI/KIA', '58101-1RA00', 'Колодки тормозные передние', 'brake-pads', ['solaris-16', 'rio-16'], [
                ['TRW', 'GDB3473', 'Колодки тормозные дисковые'],
                ['BREMBO', 'P 30 055', 'Колодки тормозные передние'],
            ]],
            ['HYUNDAI/KIA', '54651-4L000', 'Амортизатор передний левый', 'shock-absorbers', ['solaris-16', 'rio-16'], [
                ['KAYABA', '339276', 'Амортизатор газомасляный передний левый'],
            ]],
        ];

        foreach ($catalog as [$brand, $article, $name, $category, $applicability, $analogs]) {
            $original = $this->product($brands[$brand], $article, $name, $categories[$category]);
            $original->modifications()->syncWithoutDetaching(array_map(fn ($key) => $modifications[$key], $applicability));
            $this->offers($original, $suppliers, oem: true);

            foreach ($analogs as [$analogBrand, $analogArticle, $analogName]) {
                $analog = $this->product($brands[$analogBrand], $analogArticle, $analogName, $categories[$category]);
                $analog->modifications()->syncWithoutDetaching(array_map(fn ($key) => $modifications[$key], $applicability));
                $original->addAnalog($analog, 'demo');
                $this->offers($analog, $suppliers, oem: false);
            }
        }

        Customer::updateOrCreate(
            ['email' => 'customer@avto.local'],
            ['name' => 'Иван Петров', 'phone' => '+79990000000', 'password' => 'password', 'discount_percent' => 5],
        );
    }

    /** @return array<string, Brand> */
    private function brands(): array
    {
        $data = [
            'TOYOTA' => ['Япония', true, ['TOYOTA/LEXUS', 'LEXUS']],
            'HYUNDAI/KIA' => ['Корея', true, ['HYUNDAI', 'KIA', 'MOBIS', 'HYUNDAI-KIA']],
            'BOSCH' => ['Германия', false, []],
            'MANN-FILTER' => ['Германия', false, ['MANN']],
            'BREMBO' => ['Италия', false, []],
            'TRW' => ['Германия', false, ['TRW AUTOMOTIVE']],
            'NGK' => ['Япония', false, []],
            'DENSO' => ['Япония', false, []],
            'FEBI' => ['Германия', false, ['FEBI BILSTEIN']],
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
            'Фильтры' => [
                'oil-filters' => 'Масляные фильтры',
                'air-filters' => 'Воздушные фильтры',
                'cabin-filters' => 'Салонные фильтры',
            ],
            'Тормозная система' => [
                'brake-pads' => 'Тормозные колодки',
                'brake-discs' => 'Тормозные диски',
            ],
            'Зажигание' => ['spark-plugs' => 'Свечи зажигания'],
            'Подвеска' => ['shock-absorbers' => 'Амортизаторы'],
        ];

        $categories = [];
        $sort = 0;
        foreach ($tree as $parentName => $children) {
            $parent = Category::firstOrCreate(['name' => $parentName], ['sort' => $sort++]);
            foreach ($children as $slug => $name) {
                $categories[$slug] = Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'parent_id' => $parent->id]);
            }
        }

        return $categories;
    }

    /** @return array<string, int> */
    private function vehicles(): array
    {
        $data = [
            'Toyota' => [
                'Camry (XV70)' => [2017, 2024, [
                    'camry-25' => ['2.5 (181 л.с.)', 'A25A-FKS', 2.5, 181],
                    'camry-35' => ['3.5 V6 (249 л.с.)', '2GR-FKS', 3.5, 249],
                ]],
                'RAV4 (XA50)' => [2019, null, [
                    'rav4-20' => ['2.0 (149 л.с.)', 'M20A-FKS', 2.0, 149],
                    'rav4-25' => ['2.5 (199 л.с.)', 'A25A-FKS', 2.5, 199],
                ]],
            ],
            'Hyundai' => [
                'Solaris II' => [2017, 2022, ['solaris-16' => ['1.6 (123 л.с.)', 'G4FG', 1.6, 123]]],
            ],
            'Kia' => [
                'Rio IV' => [2017, 2022, ['rio-16' => ['1.6 (123 л.с.)', 'G4FG', 1.6, 123]]],
            ],
        ];

        $ids = [];
        foreach ($data as $makeName => $models) {
            $make = CarMake::firstOrCreate(['name' => $makeName]);
            foreach ($models as $modelName => [$from, $to, $mods]) {
                $model = $make->models()->firstOrCreate(['name' => $modelName], ['year_from' => $from, 'year_to' => $to]);
                foreach ($mods as $key => [$name, $engine, $volume, $hp]) {
                    $ids[$key] = $model->modifications()->firstOrCreate(['name' => $name], [
                        'engine_code' => $engine, 'engine_volume' => $volume, 'power_hp' => $hp,
                        'fuel' => 'Бензин', 'year_from' => $from, 'year_to' => $to,
                    ])->id;
                }
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

    private function product(Brand $brand, string $article, string $name, Category $category): Product
    {
        return Product::firstOrCreate(
            ['brand_id' => $brand->id, 'article_normalized' => Article::normalize($article)],
            ['article' => $article, 'name' => $name, 'category_id' => $category->id],
        );
    }

    /** @param array<string, Supplier> $suppliers */
    private function offers(Product $product, array $suppliers, bool $oem): void
    {
        // Оригинал дороже, аналоги дешевле — как в жизни.
        $base = $oem ? random_int(1500, 9000) : random_int(400, 5000);

        foreach ($suppliers as $key => $supplier) {
            if ($key === 'warehouse' && random_int(0, 1) === 0) {
                continue;
            }

            Offer::updateOrCreate(
                ['supplier_id' => $supplier->id, 'product_id' => $product->id],
                [
                    'purchase_price' => round($base * (0.9 + random_int(0, 25) / 100), 2),
                    'quantity' => random_int(1, 40),
                    'delivery_days' => $key === 'warehouse' ? 0 : random_int(1, 5),
                ],
            );
        }
    }
}

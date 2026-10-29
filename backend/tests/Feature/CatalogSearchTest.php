<?php

namespace Tests\Feature;

use App\Models\CarModification;
use App\Models\Product;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CatalogSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoCatalogSeeder::class);
    }

    /**
     * Колодки ВАЗ лежат в каталоге как 21080-3501080-00 —
     * покупатель может набрать номер в любом из ходовых форматов.
     */
    #[DataProvider('padQueries')]
    public function test_vaz_number_found_in_any_format(string $query): void
    {
        $this->getJson('/api/v1/search?q='.urlencode($query))
            ->assertOk()
            ->assertJsonFragment(['article' => '21080-3501080-00']);
    }

    public static function padQueries(): array
    {
        return [
            'как в каталоге' => ['21080-3501080-00'],
            'старый формат' => ['2108-3501080'],
            'без суффикса' => ['21080-3501080'],
            'слитно' => ['21080350108000'],
        ];
    }

    public function test_vaz_stem_does_not_match_other_parts(): void
    {
        $articles = collect($this->getJson('/api/v1/search?q=2108-3501080')->json('data'))->pluck('article');

        $this->assertNotContains('21080-3501070-00', $articles); // тормозной диск — другой номер
    }

    public function test_product_shows_offers_and_analogs(): void
    {
        $product = Product::where('article', '21080-3501080-00')->firstOrFail();

        $response = $this->getJson("/api/v1/products/{$product->id}")->assertOk();

        $this->assertNotEmpty($response->json('data.product.offers'));
        $this->assertContains('BP 43004', collect($response->json('data.analogs'))->pluck('article'));
        // Поставщик покупателю не раскрывается.
        $this->assertArrayNotHasKey('supplier', $response->json('data.product.offers.0'));
    }

    public function test_category_filtered_by_vehicle(): void
    {
        $vesta = CarModification::where('engine_code', '21129')->firstOrFail();

        $articles = collect($this->getJson("/api/v1/categories/brake-pads/products?modification={$vesta->id}")->json('data'))
            ->pluck('article');

        $this->assertContains('8450108101', $articles);
        $this->assertNotContains('21080-3501080-00', $articles);
    }
}

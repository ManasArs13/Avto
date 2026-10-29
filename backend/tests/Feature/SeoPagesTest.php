<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoCatalogSeeder::class);
    }

    public function test_model_page_lists_categories_with_parts(): void
    {
        $response = $this->getJson('/api/v1/vehicles/makes/lada/models/vesta')
            ->assertOk()
            ->assertJsonPath('data.model.name', 'Vesta');

        $categories = collect($response->json('data.categories'));
        $this->assertContains('brake-pads', $categories->pluck('slug'));
        $this->assertTrue($categories->every(fn ($c) => $c['products_count'] > 0));
        $this->assertNotEmpty($response->json('data.modifications'));
    }

    public function test_unknown_model_is_404(): void
    {
        $this->getJson('/api/v1/vehicles/makes/lada/models/camry')->assertNotFound();
    }

    public function test_category_filtered_by_model(): void
    {
        $vesta = $this->getJson('/api/v1/vehicles/makes/lada/models/vesta')->json('data.model.id');

        $articles = collect($this->getJson("/api/v1/categories/brake-pads/products?model={$vesta}")->json('data'))
            ->pluck('article');

        $this->assertContains('8450108101', $articles);
        $this->assertNotContains('21080-3501080-00', $articles);
    }

    public function test_product_lists_fitting_models(): void
    {
        $product = Product::where('article', '21080-3501080-00')->firstOrFail();

        $fits = collect($this->getJson("/api/v1/products/{$product->id}")->json('data.fits'))->pluck('model.slug');

        $this->assertContains('granta', $fits);
        $this->assertNotContains('vesta', $fits);
    }

    public function test_sitemap(): void
    {
        $data = $this->getJson('/api/v1/sitemap')->assertOk()->json('data');

        $this->assertSame(1, $data['product_pages']);
        $vesta = collect($data['models'])->firstWhere('model', 'vesta');
        $this->assertContains('brake-pads', $vesta['categories']);

        $products = $this->getJson('/api/v1/sitemap/products/1')->assertOk()->json('data');
        $this->assertNotEmpty($products);
        $this->assertCount(2, $products[0]);
    }
}

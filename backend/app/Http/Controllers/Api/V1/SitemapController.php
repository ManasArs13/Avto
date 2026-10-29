<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

/**
 * Данные для sitemap.xml витрины. Товаров может быть сотни тысяч,
 * поэтому они отдаются страницами (один файл sitemap — до 50 000 ссылок).
 */
class SitemapController extends Controller
{
    public const PRODUCTS_PER_PAGE = 40000;

    /** GET /sitemap — страницы моделей, категорий и число страниц с товарами. */
    public function index(): JsonResponse
    {
        $models = CarModel::query()->with('make:id,slug')->orderBy('id')->get();
        $categories = Category::query()->where('is_active', true)->whereNotNull('parent_id')->get(['id', 'slug']);

        // Пары «модель × категория», где есть товары в наличии: /cars/lada/vesta/brake-pads.
        $pairs = Product::query()
            ->available()
            ->join('car_modification_product', 'car_modification_product.product_id', '=', 'products.id')
            ->join('car_modifications', 'car_modifications.id', '=', 'car_modification_product.car_modification_id')
            ->whereNotNull('products.category_id')
            ->distinct()
            ->get(['car_modifications.car_model_id', 'products.category_id'])
            ->groupBy('car_model_id');

        $categorySlugs = $categories->pluck('slug', 'id');

        return response()->json([
            'data' => [
                'categories' => $categorySlugs->values(),
                'models' => $models->map(fn (CarModel $model) => [
                    'make' => $model->make->slug,
                    'model' => $model->slug,
                    'categories' => ($pairs[$model->id] ?? collect())
                        ->map(fn ($row) => $categorySlugs[$row->category_id] ?? null)
                        ->filter()
                        ->values(),
                ]),
                'product_pages' => (int) ceil(Product::query()->available()->count() / self::PRODUCTS_PER_PAGE),
            ],
        ]);
    }

    /** GET /sitemap/products/{page} — id и дата изменения товаров в наличии. */
    public function products(int $page): JsonResponse
    {
        abort_if($page < 1, 404);

        $products = Product::query()
            ->available()
            ->orderBy('id')
            ->forPage($page, self::PRODUCTS_PER_PAGE)
            ->toBase()
            ->get(['id', 'updated_at'])
            ->map(fn ($p) => [$p->id, substr((string) $p->updated_at, 0, 10)]);

        return response()->json(['data' => $products]);
    }
}

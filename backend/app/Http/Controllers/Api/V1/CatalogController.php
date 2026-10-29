<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\Product;
use App\Services\CatalogSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogController extends Controller
{
    public function __construct(private CatalogSearch $search) {}

    public function brands(): AnonymousResourceCollection
    {
        return BrandResource::collection(Brand::orderBy('name')->get());
    }

    public function categories(): AnonymousResourceCollection
    {
        $tree = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort')
            ->get();

        return CategoryResource::collection($tree);
    }

    /**
     * GET /search?q=04465-33450 — список брендов с этим артикулом (или поиск по названию).
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);

        return ProductResource::collection($this->search->search($request->string('q')));
    }

    /**
     * GET /products/{id} — товар, его предложения и аналоги с предложениями.
     */
    public function product(Product $product): JsonResponse
    {
        abort_unless($product->is_active, 404);

        ['product' => $product, 'offers' => $offers, 'analogs' => $analogs] = $this->search->offersFor($product);
        $product->setRelation('offers', $offers);

        // Модели, к которым подходит деталь: ссылки на SEO-страницы «Запчасти LADA Vesta».
        $fits = CarModel::query()
            ->whereHas('modifications.products', fn ($q) => $q->whereKey($product->id))
            ->with('make:id,name,slug')
            ->orderBy('name')
            ->get()
            ->map(fn (CarModel $model) => [
                'make' => $model->make->only('name', 'slug'),
                'model' => $model->only('name', 'slug'),
            ]);

        return response()->json([
            'data' => [
                'product' => new ProductResource($product),
                'analogs' => ProductResource::collection($analogs),
                'fits' => $fits,
            ],
        ]);
    }

    /**
     * GET /categories/{slug}/products?modification=ID|model=ID — товары категории, опционально для конкретного авто или модели.
     */
    public function categoryProducts(Request $request, Category $category): AnonymousResourceCollection
    {
        $categoryIds = $category->children()->pluck('id')->push($category->id);

        $products = Product::query()
            ->available()
            ->whereIn('category_id', $categoryIds)
            ->when($request->integer('modification'), fn ($q, $id) => $q->whereHas('modifications', fn ($q) => $q->whereKey($id)))
            ->when($request->integer('model'), fn ($q, $id) => $q->forCarModel($id))
            ->with('brand')
            ->orderBy('name')
            ->paginate(30);

        return ProductResource::collection($this->search->withBestOffers($products));
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Brand;
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

        return response()->json([
            'data' => [
                'product' => new ProductResource($product),
                'analogs' => ProductResource::collection($analogs),
            ],
        ]);
    }

    /**
     * GET /categories/{slug}/products?modification=ID — товары категории, опционально для конкретного авто.
     */
    public function categoryProducts(Request $request, Category $category): AnonymousResourceCollection
    {
        $categoryIds = $category->children()->pluck('id')->push($category->id);

        $products = Product::query()
            ->where('is_active', true)
            ->whereIn('category_id', $categoryIds)
            ->when($request->integer('modification'), fn ($q, $id) => $q->whereHas('modifications', fn ($q) => $q->whereKey($id)))
            ->whereHas('offers', fn ($q) => $q->where('quantity', '>', 0))
            ->with('brand')
            ->orderBy('name')
            ->paginate(30);

        return ProductResource::collection($this->search->withBestOffers($products));
    }
}

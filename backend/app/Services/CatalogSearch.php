<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Product;
use App\Support\Article;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

class CatalogSearch
{
    public function __construct(private PriceCalculator $prices) {}

    /**
     * Шаг 1 поиска: по артикулу находим все бренды, у которых он есть
     * ("уточните производителя"). Если по артикулу пусто — ищем по названию.
     *
     * @return Collection<int, Product>
     */
    public function search(string $query, int $limit = 30): Collection
    {
        $normalized = Article::normalize($query);

        $products = collect();
        if (mb_strlen($normalized) >= 3) {
            $products = Product::query()
                ->where('is_active', true)
                ->where('article_normalized', $normalized)
                ->with('brand')
                ->limit($limit)
                ->get();
        }

        if ($products->isEmpty()) {
            $products = Product::search($query)
                ->where('is_active', true)
                ->take($limit)
                ->query(fn ($q) => $q->with('brand'))
                ->get();
        }

        return $this->withBestOffers($products);
    }

    /**
     * Шаг 2: предложения по товару и по всем его аналогам.
     *
     * @return array{product: Product, offers: Collection, analogs: Collection}
     */
    public function offersFor(Product $product): array
    {
        $product->load(['brand', 'category', 'analogs.brand']);

        $offers = Offer::query()
            ->whereIn('product_id', $product->analogs->pluck('id')->push($product->id))
            ->where('quantity', '>', 0)
            ->with('supplier:id,type,delivery_days,is_active', 'product:id,brand_id')
            ->get()
            ->filter(fn (Offer $offer) => $offer->supplier->is_active)
            ->each(fn (Offer $offer) => $offer->setAttribute('sale_price', $this->prices->forOffer($offer)))
            ->sortBy(fn (Offer $o) => [$o->sale_price, $o->totalDeliveryDays()])
            ->groupBy('product_id');

        $analogs = $product->analogs
            ->each(fn (Product $analog) => $analog->setRelation('offers', $offers->get($analog->id, collect())->values()))
            ->filter(fn (Product $analog) => $analog->offers->isNotEmpty())
            ->sortBy(fn (Product $analog) => $analog->offers->first()->sale_price)
            ->values();

        return [
            'product' => $product,
            'offers' => $offers->get($product->id, collect())->values(),
            'analogs' => $analogs,
        ];
    }

    /**
     * Добавляет к товарам min_price, min_delivery_days и offers_count для карточек в списках.
     *
     * @template T of Collection|AbstractPaginator
     *
     * @param  T  $products
     * @return T
     */
    public function withBestOffers(Collection|AbstractPaginator $products): Collection|AbstractPaginator
    {
        $items = $products instanceof AbstractPaginator ? $products->getCollection() : $products;

        $offers = Offer::query()
            ->whereIn('product_id', $items->pluck('id'))
            ->where('quantity', '>', 0)
            ->whereHas('supplier', fn ($q) => $q->where('is_active', true))
            ->with('supplier:id,delivery_days', 'product:id,brand_id')
            ->get()
            ->groupBy('product_id');

        $items->each(function (Product $product) use ($offers) {
            $productOffers = $offers->get($product->id, collect());
            $product->setAttribute('offers_count', $productOffers->count());
            $product->setAttribute('min_price', $productOffers->map(fn (Offer $o) => $this->prices->forOffer($o))->min());
            $product->setAttribute('min_delivery_days', $productOffers->map(fn (Offer $o) => $o->totalDeliveryDays())->min());
        });

        return $products;
    }
}

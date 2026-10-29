<?php

namespace App\Services;

use App\Models\MarkupRule;
use App\Models\Offer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PriceCalculator
{
    public const CACHE_KEY = 'markup_rules';

    // Если ни одно правило не подошло.
    private const DEFAULT_PERCENT = 30.0;

    private ?Collection $rules = null;

    public function forOffer(Offer $offer): float
    {
        $offer->loadMissing('product:id,brand_id');

        return $this->calculate(
            (float) $offer->purchase_price,
            $offer->supplier_id,
            $offer->product->brand_id,
        );
    }

    public function calculate(float $purchasePrice, int $supplierId, int $brandId, float $discountPercent = 0): float
    {
        $rule = $this->rules()->first(fn (MarkupRule $rule) => $rule->matches($supplierId, $brandId, $purchasePrice));

        $percent = $rule ? (float) $rule->percent : self::DEFAULT_PERCENT;
        $price = $purchasePrice * (1 + $percent / 100) * (1 - $discountPercent / 100);

        // Розница — целые рубли, округляем вверх.
        return (float) ceil($price);
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function rules(): Collection
    {
        // В кэше храним сырые атрибуты: Laravel не десериализует объекты моделей из кэша.
        return $this->rules ??= MarkupRule::hydrate(Cache::remember(
            self::CACHE_KEY,
            now()->addHour(),
            fn () => MarkupRule::where('is_active', true)
                ->orderByDesc('priority')
                ->orderByDesc('price_from')
                ->get()
                ->map(fn (MarkupRule $rule) => $rule->getAttributes())
                ->all(),
        ));
    }
}

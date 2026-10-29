<?php

namespace App\Models;

use App\Support\Article;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

#[Fillable([
    'brand_id', 'category_id', 'article', 'name', 'description',
    'weight', 'specs', 'images', 'is_active',
])]
class Product extends Model
{
    use Searchable;

    protected function casts(): array
    {
        return [
            'specs' => 'array',
            'images' => 'array',
            'is_active' => 'boolean',
            'weight' => 'decimal:3',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (Product $product) => $product->article_normalized = Article::normalize($product->article));
    }

    /** Товар активен и есть хотя бы у одного поставщика — только такие показываем в каталоге. */
    #[Scope]
    protected function available(Builder $query): void
    {
        $query->where('is_active', true)->whereHas('offers', fn ($q) => $q->where('quantity', '>', 0));
    }

    /** Подходит хотя бы к одной модификации модели. */
    #[Scope]
    protected function forCarModel(Builder $query, int $modelId): void
    {
        $query->whereHas('modifications', fn ($q) => $q->where('car_model_id', $modelId));
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function analogs(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'crosses', 'product_id', 'analog_id')
            ->withPivot('source')
            ->withTimestamps();
    }

    public function modifications(): BelongsToMany
    {
        return $this->belongsToMany(CarModification::class);
    }

    /**
     * Связывает два товара как аналоги в обе стороны.
     */
    public function addAnalog(Product $analog, string $source = 'manual'): void
    {
        if ($analog->is($this)) {
            return;
        }

        $this->analogs()->syncWithoutDetaching([$analog->id => ['source' => $source]]);
        $analog->analogs()->syncWithoutDetaching([$this->id => ['source' => $source]]);
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing('brand', 'category');

        return [
            'id' => $this->id,
            'article' => $this->article,
            'article_normalized' => $this->article_normalized,
            'name' => $this->name,
            'brand' => $this->brand?->name,
            'category' => $this->category?->name,
            'is_active' => $this->is_active,
        ];
    }

    protected function makeAllSearchableUsing($query)
    {
        return $query->with('brand', 'category');
    }
}

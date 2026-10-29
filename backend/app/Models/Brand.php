<?php

namespace App\Models;

use App\Support\Article;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'country', 'is_oem', 'logo'])]
class Brand extends Model
{
    protected function casts(): array
    {
        return ['is_oem' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(fn (Brand $brand) => $brand->slug ??= Str::slug($brand->name));

        // Собственное название бренда всегда работает как алиас.
        static::saved(fn (Brand $brand) => $brand->aliases()->firstOrCreate([
            'alias' => Article::normalizeBrand($brand->name),
        ]));
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(BrandAlias::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}

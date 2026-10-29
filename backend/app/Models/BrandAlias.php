<?php

namespace App\Models;

use App\Support\Article;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['brand_id', 'alias'])]
class BrandAlias extends Model
{
    protected static function booted(): void
    {
        static::saving(fn (BrandAlias $alias) => $alias->alias = Article::normalizeBrand($alias->alias));
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug'])]
class CarMake extends Model
{
    protected static function booted(): void
    {
        static::saving(fn (CarMake $make) => $make->slug ??= Str::slug($make->name));
    }

    public function models(): HasMany
    {
        return $this->hasMany(CarModel::class)->orderBy('name');
    }
}

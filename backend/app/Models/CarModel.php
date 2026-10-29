<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['car_make_id', 'name', 'slug', 'year_from', 'year_to'])]
class CarModel extends Model
{
    protected static function booted(): void
    {
        static::saving(fn (CarModel $model) => $model->slug ??= Str::slug($model->name));
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(CarMake::class, 'car_make_id');
    }

    public function modifications(): HasMany
    {
        return $this->hasMany(CarModification::class)->orderBy('name');
    }
}

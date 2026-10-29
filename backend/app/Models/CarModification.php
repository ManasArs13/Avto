<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'car_model_id', 'name', 'engine_code', 'engine_volume',
    'power_hp', 'fuel', 'year_from', 'year_to',
])]
class CarModification extends Model
{
    public function model(): BelongsTo
    {
        return $this->belongsTo(CarModel::class, 'car_model_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** «LADA Vesta 1.6 16V (106 л.с.)» — нужна загруженная связь model.make. */
    public function title(): string
    {
        return "{$this->model->make->name} {$this->model->name} {$this->name}";
    }
}

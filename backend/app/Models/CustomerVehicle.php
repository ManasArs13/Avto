<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Автомобиль в гараже покупателя. */
#[Fillable(['car_modification_id', 'name', 'vin'])]
class CustomerVehicle extends Model
{
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function modification(): BelongsTo
    {
        return $this->belongsTo(CarModification::class, 'car_modification_id');
    }
}

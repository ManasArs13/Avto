<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

// Покупатель. Авторизация через Sanctum-токены (сайт и мобильное приложение).
#[Fillable(['name', 'email', 'phone', 'password', 'discount_percent'])]
#[Hidden(['password'])]
class Customer extends Authenticatable
{
    use HasApiTokens;

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'discount_percent' => 'decimal:2',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(CustomerVehicle::class)->latest('id');
    }
}

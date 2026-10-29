<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModification;
use App\Models\Category;
use App\Models\Customer;
use App\Models\MarkupRule;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PriceImport;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Policies\StaffPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Права сотрудников в админке (роли — App\Enums\StaffRole).
        $models = [
            Brand::class, Category::class, Product::class, Offer::class,
            Supplier::class, PriceImport::class, MarkupRule::class,
            CarMake::class, CarModel::class, CarModification::class,
            Customer::class, Order::class, OrderItem::class,
        ];

        foreach ($models as $model) {
            Gate::policy($model, StaffPolicy::class);
        }

        Gate::policy(User::class, UserPolicy::class);
    }
}

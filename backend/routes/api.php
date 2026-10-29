<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\GarageController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\SitemapController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Support\Facades\Route;

/*
| Публичный API для сайта (Next.js) и мобильного приложения.
| Версионируем с первого дня: старые версии приложения живут в сторах годами.
*/
Route::prefix('v1')->group(function () {
    // Каталог
    Route::get('brands', [CatalogController::class, 'brands']);
    Route::get('categories', [CatalogController::class, 'categories']);
    Route::get('categories/{category:slug}/products', [CatalogController::class, 'categoryProducts']);
    // Без throttle: поиск рендерит сервер Next.js, и все покупатели пришли бы с одного IP.
    Route::get('search', [CatalogController::class, 'search']);
    Route::get('products/{product}', [CatalogController::class, 'product']);

    // Для sitemap.xml витрины
    Route::get('sitemap', [SitemapController::class, 'index']);
    Route::get('sitemap/products/{page}', [SitemapController::class, 'products'])->whereNumber('page');

    // Подбор по авто
    Route::get('vehicles/makes', [VehicleController::class, 'makes']);
    Route::get('vehicles/makes/{make}/models', [VehicleController::class, 'models']);
    Route::get('vehicles/makes/{make:slug}/models/{model:slug}', [VehicleController::class, 'model']);
    Route::get('vehicles/models/{model}/modifications', [VehicleController::class, 'modifications']);
    Route::get('vehicles/modifications/{modification}', [VehicleController::class, 'modification']);

    // Покупатели
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    // Заказы: гость тоже может оформить и посмотреть заказ (по номеру + телефону)
    Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:20,1');
    Route::get('orders/{order}', [OrderController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::patch('auth/me', [AuthController::class, 'update'])->middleware('throttle:10,1');
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('orders', [OrderController::class, 'index']);

        // Мой гараж
        Route::get('garage', [GarageController::class, 'index']);
        Route::post('garage', [GarageController::class, 'store']);
        Route::delete('garage/{vehicle}', [GarageController::class, 'destroy'])->whereNumber('vehicle');
    });
});

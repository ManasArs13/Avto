<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModification;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

/**
 * Подбор по автомобилю: марка → модель → модификация.
 */
class VehicleController extends Controller
{
    public function makes(): JsonResponse
    {
        return response()->json(['data' => CarMake::orderBy('name')->get(['id', 'name', 'slug'])]);
    }

    public function models(CarMake $make): JsonResponse
    {
        return response()->json(['data' => $make->models()->get(['id', 'name', 'slug', 'year_from', 'year_to'])]);
    }

    /**
     * GET /vehicles/makes/lada/models/vesta — SEO-страница «Запчасти LADA Vesta»:
     * модель, двигатели и категории, в которых есть детали для неё.
     */
    public function model(CarMake $make, CarModel $model): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->whereNotNull('parent_id')
            ->with('parent:id,name,slug,sort')
            ->withCount(['products' => fn ($q) => $q->available()->forCarModel($model->id)])
            ->orderBy('sort')
            ->get()
            ->where('products_count', '>', 0)
            ->sortBy(fn (Category $c) => [$c->parent->sort, $c->sort])
            ->map(fn (Category $c) => [
                ...$c->only('id', 'name', 'slug', 'products_count'),
                'parent' => $c->parent->only('name', 'slug'),
            ])
            ->values();

        return response()->json([
            'data' => [
                'make' => $make->only('id', 'name', 'slug'),
                'model' => $model->only('id', 'name', 'slug', 'year_from', 'year_to'),
                'modifications' => $model->modifications()->get([
                    'id', 'name', 'engine_code', 'engine_volume', 'power_hp', 'fuel', 'year_from', 'year_to',
                ]),
                'categories' => $categories,
            ],
        ]);
    }

    public function modifications(CarModel $model): JsonResponse
    {
        return response()->json([
            'data' => $model->modifications()->get([
                'id', 'name', 'engine_code', 'engine_volume', 'power_hp', 'fuel', 'year_from', 'year_to',
            ]),
        ]);
    }

    public function modification(CarModification $modification): JsonResponse
    {
        $modification->load('model.make');

        return response()->json([
            'data' => [
                'id' => $modification->id,
                'name' => $modification->name,
                'title' => $modification->title(),
                'make' => $modification->model->make->only('id', 'name', 'slug'),
                'model' => $modification->model->only('id', 'name', 'slug'),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarModification;
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
                'title' => "{$modification->model->make->name} {$modification->model->name} {$modification->name}",
                'make' => $modification->model->make->only('id', 'name'),
                'model' => $modification->model->only('id', 'name'),
            ],
        ]);
    }
}

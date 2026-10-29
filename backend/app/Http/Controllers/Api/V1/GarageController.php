<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomerVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * «Мой гараж» — сохранённые автомобили покупателя.
 * Один и тот же список на сайте и в приложении.
 */
class GarageController extends Controller
{
    private const LIMIT = 20;

    public function index(Request $request): JsonResponse
    {
        $vehicles = $request->user()->vehicles()->with('modification.model.make')->get();

        return response()->json(['data' => $vehicles->map($this->vehicleData(...))]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'car_modification_id' => ['required', 'integer', 'exists:car_modifications,id'],
            'name' => ['nullable', 'string', 'max:100'],
            'vin' => ['nullable', 'string', 'size:17', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/i'],
        ]);

        $customer = $request->user();
        $vehicle = $customer->vehicles()->firstWhere('car_modification_id', $data['car_modification_id']);

        if (! $vehicle && $customer->vehicles()->count() >= self::LIMIT) {
            throw ValidationException::withMessages(['car_modification_id' => 'В гараже не больше '.self::LIMIT.' автомобилей.']);
        }

        $vehicle ??= $customer->vehicles()->make(['car_modification_id' => $data['car_modification_id']]);
        $vehicle->fill([
            'name' => $data['name'] ?? $vehicle->name,
            'vin' => isset($data['vin']) ? strtoupper($data['vin']) : $vehicle->vin,
        ])->save();

        return response()->json(['data' => $this->vehicleData($vehicle->load('modification.model.make'))], $vehicle->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, int $vehicle): JsonResponse
    {
        $request->user()->vehicles()->whereKey($vehicle)->firstOrFail()->delete();

        return response()->json(['message' => 'ok']);
    }

    private function vehicleData(CustomerVehicle $vehicle): array
    {
        $modification = $vehicle->modification;

        return [
            'id' => $vehicle->id,
            'name' => $vehicle->name,
            'vin' => $vehicle->vin,
            'modification' => [
                'id' => $modification->id,
                'name' => $modification->name,
                'title' => $modification->title(),
                'make' => $modification->model->make->only('id', 'name', 'slug'),
                'model' => $modification->model->only('id', 'name', 'slug'),
            ],
        ];
    }
}

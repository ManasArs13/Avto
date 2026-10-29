<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Авторизация покупателей по Bearer-токену (Sanctum).
 * Один и тот же механизм для сайта и мобильного приложения.
 */
class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['required', Password::min(8)],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $customer = Customer::create($data);

        return $this->tokenResponse($customer, $data['device_name'] ?? 'web', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $customer = Customer::where('email', $data['email'])->first();

        if (! $customer || ! Hash::check($data['password'], $customer->password)) {
            throw ValidationException::withMessages(['email' => 'Неверный email или пароль.']);
        }

        return $this->tokenResponse($customer, $data['device_name'] ?? 'web');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'ok']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->customerData($request->user())]);
    }

    private function tokenResponse(Customer $customer, string $device, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => [
                'token' => $customer->createToken($device)->plainTextToken,
                'customer' => $this->customerData($customer),
            ],
        ], $status);
    }

    private function customerData(Customer $customer): array
    {
        return $customer->only('id', 'name', 'email', 'phone', 'discount_percent');
    }
}

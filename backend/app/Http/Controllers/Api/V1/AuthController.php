<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ConsentType;
use App\Http\Controllers\Controller;
use App\Models\Consent;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            // 152-ФЗ: отдельное согласие на обработку ПДн, галочка не может стоять заранее.
            'consent_personal_data' => ['accepted'],
            'consent_marketing' => ['sometimes', 'boolean'],
        ], [
            'consent_personal_data.accepted' => 'Нужно согласие на обработку персональных данных.',
        ]);

        $marketing = (bool) ($data['consent_marketing'] ?? false);
        $customer = DB::transaction(function () use ($data, $marketing, $request) {
            $customer = Customer::create([...$data, 'marketing_consent' => $marketing]);
            Consent::record(ConsentType::PersonalData, true, $request, $customer);
            if ($marketing) {
                Consent::record(ConsentType::Marketing, true, $request, $customer);
            }

            return $customer;
        });

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

    /** PATCH /auth/me — имя, телефон и (с текущим паролем) новый пароль. */
    public function update(Request $request): JsonResponse
    {
        $customer = $request->user();
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'current_password' => ['required_with:password', 'string'],
            'password' => ['sometimes', Password::min(8)],
            'marketing_consent' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['password']) && ! Hash::check($data['current_password'], $customer->password)) {
            throw ValidationException::withMessages(['current_password' => 'Неверный текущий пароль.']);
        }

        // Дать или отозвать согласие на рассылки — фиксируем в журнале.
        if (isset($data['marketing_consent']) && $data['marketing_consent'] !== $customer->marketing_consent) {
            Consent::record(ConsentType::Marketing, $data['marketing_consent'], $request, $customer);
        }

        $customer->update(collect($data)->except('current_password')->all());

        return response()->json(['data' => $this->customerData($customer)]);
    }

    /**
     * DELETE /auth/me — удаление аккаунта и отзыв согласия на обработку ПДн (152-ФЗ, ст. 9 и 21).
     * Удаляются профиль, токены и гараж. Заказы остаются без привязки к аккаунту:
     * их храним для исполнения договора и бухгалтерского учёта.
     */
    public function destroy(Request $request): JsonResponse
    {
        $customer = $request->user();
        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->string('password'), $customer->password)) {
            throw ValidationException::withMessages(['password' => 'Неверный пароль.']);
        }

        DB::transaction(function () use ($customer, $request) {
            // Отзыв — последняя запись в журнале; после удаления аккаунта она остаётся без привязки к нему.
            Consent::record(ConsentType::PersonalData, false, $request, $customer);
            $customer->tokens()->delete();
            $customer->delete();
        });

        return response()->json(['message' => 'ok']);
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
        return $customer->only('id', 'name', 'email', 'phone', 'discount_percent', 'marketing_consent');
    }
}

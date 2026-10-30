<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ConsentType;
use App\Enums\DeliveryMethod;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Consent;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    /**
     * Оформление заказа. Работает и для гостя, и с Bearer-токеном покупателя.
     */
    public function store(Request $request): OrderResource
    {
        // Маршрут публичный, поэтому токен проверяем вручную: гость тоже может оформить заказ.
        $customer = Auth::guard('sanctum')->user();

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.offer_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:32'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'delivery_method' => ['required', Rule::enum(DeliveryMethod::class)],
            'delivery_address' => [
                Rule::requiredIf(fn () => $request->input('delivery_method') !== DeliveryMethod::Pickup->value),
                'nullable', 'string', 'max:500',
            ],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'comment' => ['nullable', 'string', 'max:2000'],
            // Гость даёт согласие на обработку ПДн при заказе; покупатель с аккаунтом — дал при регистрации.
            // (accepted проверяется и для отсутствующего поля, поэтому для покупателя поле исключаем.)
            'consent_personal_data' => $customer ? ['exclude'] : ['accepted'],
        ], [
            'consent_personal_data.accepted' => 'Нужно согласие на обработку персональных данных.',
        ]);

        $order = $this->orders->create($data, $customer);
        if (! $customer) {
            Consent::record(ConsentType::PersonalData, true, $request, order: $order);
        }

        return new OrderResource($order);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection($request->user()->orders()->paginate(20));
    }

    /**
     * Статус заказа по номеру + телефону — для гостей без аккаунта.
     */
    public function show(Request $request, Order $order): OrderResource
    {
        $customer = Auth::guard('sanctum')->user();
        $phoneMatches = $request->filled('phone')
            && preg_replace('/\D/', '', $request->string('phone')) === preg_replace('/\D/', '', $order->contact_phone);

        abort_unless(($customer && $order->customer_id === $customer->id) || $phoneMatches, 404);

        return new OrderResource($order->load('items'));
    }
}

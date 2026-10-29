<?php

namespace App\Services;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Offer;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private PriceCalculator $prices) {}

    /**
     * Цены всегда пересчитываются на сервере — клиенту не доверяем.
     *
     * @param  array{items: array<array{offer_id: int, quantity: int}>, contact_name: string, contact_phone: string, contact_email?: string, delivery_method: string, delivery_address?: string, payment_method: string, comment?: string}  $data
     */
    public function create(array $data, ?Customer $customer = null): Order
    {
        $offers = Offer::with('product.brand', 'supplier')
            ->whereIn('id', array_column($data['items'], 'offer_id'))
            ->get()
            ->keyBy('id');

        $discount = (float) ($customer?->discount_percent ?? 0);
        $items = [];

        foreach ($data['items'] as $i => $item) {
            $offer = $offers->get($item['offer_id']);

            if (! $offer || ! $offer->supplier->is_active) {
                throw ValidationException::withMessages(["items.$i.offer_id" => 'Предложение больше недоступно.']);
            }
            if ($item['quantity'] > $offer->quantity) {
                throw ValidationException::withMessages(["items.$i.quantity" => "Доступно не больше {$offer->quantity} шт."]);
            }

            $items[] = [
                'product_id' => $offer->product_id,
                'supplier_id' => $offer->supplier_id,
                'brand_name' => $offer->product->brand->name,
                'article' => $offer->product->article,
                'name' => $offer->product->name,
                'purchase_price' => $offer->purchase_price,
                'price' => $this->prices->calculate((float) $offer->purchase_price, $offer->supplier_id, $offer->product->brand_id, $discount),
                'quantity' => $item['quantity'],
                'delivery_days' => $offer->totalDeliveryDays(),
            ];
        }

        $subtotal = collect($items)->sum(fn ($i) => $i['price'] * $i['quantity']);
        $delivery = DeliveryMethod::from($data['delivery_method']);
        // TODO: расчёт через API СДЭК. Пока фиксированные тарифы.
        $deliveryCost = match ($delivery) {
            DeliveryMethod::Pickup => 0,
            DeliveryMethod::Courier => 500,
            DeliveryMethod::Cdek => 400,
        };

        return DB::transaction(function () use ($data, $customer, $items, $subtotal, $delivery, $deliveryCost) {
            $order = Order::create([
                'customer_id' => $customer?->id,
                'status' => OrderStatus::New,
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'contact_email' => $data['contact_email'] ?? $customer?->email,
                'delivery_method' => $delivery,
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_cost' => $deliveryCost,
                'payment_method' => PaymentMethod::from($data['payment_method']),
                'payment_status' => PaymentStatus::Pending,
                'subtotal' => $subtotal,
                'total' => $subtotal + $deliveryCost,
                'comment' => $data['comment'] ?? null,
            ]);
            $order->items()->createMany($items);

            return $order->load('items');
        });
    }
}

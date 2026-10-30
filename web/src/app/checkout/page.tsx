"use client";

import { ApiError, formatPrice, type DeliveryMethod, type PaymentMethod } from "@avto/shared";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { ConsentCheckbox, DocLink } from "@/components/consent-checkbox";
import { Field } from "@/components/field";
import { authApi, useSession } from "@/lib/auth";
import { cart, useCart } from "@/lib/cart";

const DELIVERY: { value: DeliveryMethod; label: string; cost: number }[] = [
  { value: "pickup", label: "Самовывоз из магазина", cost: 0 },
  { value: "courier", label: "Курьер по городу", cost: 500 },
  { value: "cdek", label: "СДЭК", cost: 400 },
];

const PAYMENT: { value: PaymentMethod; label: string }[] = [
  { value: "card", label: "Картой онлайн" },
  { value: "on_receipt", label: "При получении" },
  { value: "invoice", label: "Счёт для юрлиц" },
];

export default function CheckoutPage() {
  const router = useRouter();
  const { items, total } = useCart();
  // Если покупатель вошёл, заказ привяжется к аккаунту (токен уходит в authApi), а контакты подставятся.
  const customer = useSession()?.customer;
  const [delivery, setDelivery] = useState<DeliveryMethod>("pickup");
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [message, setMessage] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const deliveryCost = DELIVERY.find((d) => d.value === delivery)?.cost ?? 0;

  if (items.length === 0) {
    return (
      <div className="card mx-auto max-w-md p-8 text-center">
        <p className="text-muted">В корзине ничего нет.</p>
        <Link href="/" className="btn mt-4">
          К покупкам
        </Link>
      </div>
    );
  }

  async function submit(form: FormData) {
    setSubmitting(true);
    setErrors({});
    setMessage(null);
    const phone = String(form.get("contact_phone"));

    try {
      const order = await authApi.orders.create({
        items: items.map((i) => ({ offer_id: i.offerId, quantity: i.quantity })),
        contact_name: String(form.get("contact_name")),
        contact_phone: phone,
        contact_email: String(form.get("contact_email") || "") || undefined,
        delivery_method: delivery,
        delivery_address: String(form.get("delivery_address") || "") || undefined,
        payment_method: String(form.get("payment_method")) as PaymentMethod,
        comment: String(form.get("comment") || "") || undefined,
        // Покупатель с аккаунтом уже дал согласие при регистрации.
        ...(customer ? {} : { consent_personal_data: form.get("consent_personal_data") === "1" }),
      });
      cart.clear();
      router.push(`/order/${order.number}?phone=${encodeURIComponent(phone)}`);
    } catch (e) {
      if (e instanceof ApiError && e.status === 422) {
        setErrors(e.errors);
        setMessage("Проверьте поля формы.");
      } else {
        setMessage("Не удалось оформить заказ. Попробуйте ещё раз или позвоните нам.");
      }
      setSubmitting(false);
    }
  }

  // Ошибки по позициям корзины (нет в наличии и т.п.)
  const itemErrors = Object.entries(errors).filter(([k]) => k.startsWith("items."));

  return (
    <form action={submit} className="grid gap-6 lg:grid-cols-[1fr_320px]">
      <div className="space-y-6">
        <h1 className="text-2xl font-bold">Оформление заказа</h1>

        {/* key: после гидратации сессия появляется — пересоздаём поля, чтобы подставились defaultValue. */}
        <fieldset key={customer?.id ?? "guest"} className="card grid gap-3 p-5 sm:grid-cols-2">
          <legend className="sr-only">Контакты</legend>
          <Field label="Имя" name="contact_name" errors={errors} required autoComplete="name" defaultValue={customer?.name} />
          <Field
            label="Телефон"
            name="contact_phone"
            errors={errors}
            required
            type="tel"
            autoComplete="tel"
            defaultValue={customer?.phone ?? undefined}
          />
          <Field
            label="Email (для чека)"
            name="contact_email"
            errors={errors}
            type="email"
            autoComplete="email"
            defaultValue={customer?.email}
          />
        </fieldset>

        <fieldset className="card space-y-3 p-5">
          <legend className="mb-1 font-semibold">Доставка</legend>
          {DELIVERY.map((d) => (
            <label key={d.value} className="flex cursor-pointer items-center gap-3">
              <input
                type="radio"
                name="delivery_method"
                value={d.value}
                checked={delivery === d.value}
                onChange={() => setDelivery(d.value)}
                className="accent-[var(--accent)]"
              />
              <span className="flex-1">{d.label}</span>
              <span className="text-sm text-muted">{d.cost ? formatPrice(d.cost) : "бесплатно"}</span>
            </label>
          ))}
          {delivery !== "pickup" && (
            <Field label="Адрес доставки" name="delivery_address" errors={errors} required autoComplete="street-address" />
          )}
        </fieldset>

        <fieldset className="card space-y-3 p-5">
          <legend className="mb-1 font-semibold">Оплата</legend>
          {PAYMENT.map((p, i) => (
            <label key={p.value} className="flex cursor-pointer items-center gap-3">
              <input
                type="radio"
                name="payment_method"
                value={p.value}
                defaultChecked={i === 0}
                className="accent-[var(--accent)]"
              />
              {p.label}
            </label>
          ))}
        </fieldset>

        <label className="card block p-5">
          <span className="mb-2 block font-semibold">Комментарий</span>
          <textarea name="comment" rows={3} className="input" placeholder="VIN, удобное время звонка…" />
        </label>
      </div>

      <aside className="card h-fit space-y-3 p-5 lg:sticky lg:top-24">
        <div className="flex justify-between text-sm">
          <span className="text-muted">Товары ({items.length})</span>
          <span className="tabular-nums">{formatPrice(total)}</span>
        </div>
        <div className="flex justify-between text-sm">
          <span className="text-muted">Доставка</span>
          <span className="tabular-nums">{formatPrice(deliveryCost)}</span>
        </div>
        <div className="flex justify-between border-t border-border pt-3 text-lg font-bold">
          <span>Итого</span>
          <span className="tabular-nums">{formatPrice(total + deliveryCost)}</span>
        </div>
        {!customer && (
          <ConsentCheckbox name="consent_personal_data" required error={errors.consent_personal_data?.[0]}>
            Даю <DocLink slug="personal-data-consent">согласие на обработку персональных данных</DocLink>
          </ConsentCheckbox>
        )}
        <p className="text-xs text-muted">
          Нажимая «Подтвердить заказ», вы принимаете условия <DocLink slug="offer">публичной оферты</DocLink>,{" "}
          <DocLink slug="delivery">доставки и оплаты</DocLink> и <DocLink slug="returns">возврата</DocLink>.
        </p>
        {message && <p className="text-sm text-red-600">{message}</p>}
        {itemErrors.map(([k, v]) => (
          <p key={k} className="text-sm text-red-600">
            {v.join(" ")}
          </p>
        ))}
        <button type="submit" className="btn w-full py-3" disabled={submitting}>
          {submitting ? "Оформляем…" : "Подтвердить заказ"}
        </button>
      </aside>
    </form>
  );
}

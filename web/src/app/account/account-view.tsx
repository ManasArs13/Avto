"use client";

import { ApiError, formatPrice, type GarageVehicle, type Order } from "@avto/shared";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { DocLink } from "@/components/consent-checkbox";
import { Field } from "@/components/field";
import {
  authApi,
  handleUnauthorized,
  logout,
  setSession,
  updateCustomer,
  useHydrated,
  useSession,
} from "@/lib/auth";
import { setSelectedCar, useSelectedCar } from "@/lib/car";
import { carPath } from "@/lib/site";

export function AccountView() {
  const router = useRouter();
  const hydrated = useHydrated();
  const session = useSession();

  useEffect(() => {
    if (hydrated && !session) router.replace("/account/login");
  }, [hydrated, session, router]);

  if (!session) return <div className="card h-64 animate-pulse" />;

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center gap-3">
        <div className="min-w-0 flex-1">
          <h1 className="text-2xl font-bold">{session.customer.name}</h1>
          <div className="text-sm text-muted">{session.customer.email}</div>
        </div>
        <button type="button" className="btn-ghost" onClick={() => logout().then(() => router.replace("/"))}>
          Выйти
        </button>
      </div>

      <Garage />
      <Orders />
      <Profile />
      <DeleteAccount />
    </div>
  );
}

function Garage() {
  const router = useRouter();
  const selected = useSelectedCar();
  const [vehicles, setVehicles] = useState<GarageVehicle[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    authApi.garage
      .list()
      .then(setVehicles)
      .catch((e) => handleUnauthorized(e) || setError("Не удалось загрузить гараж."));
  }, []);

  async function add(modificationId: number) {
    const vehicle = await authApi.garage.add({ car_modification_id: modificationId });
    setVehicles((list) => [vehicle, ...(list ?? []).filter((v) => v.id !== vehicle.id)]);
  }

  async function remove(id: number) {
    await authApi.garage.remove(id);
    setVehicles((list) => list?.filter((v) => v.id !== id) ?? null);
  }

  function pick(vehicle: GarageVehicle) {
    const m = vehicle.modification;
    setSelectedCar({ id: m.id, title: m.title });
    router.push(carPath(m.make.slug, m.model.slug));
  }

  const selectedSaved = vehicles?.some((v) => v.modification.id === selected?.id);

  return (
    <section className="card p-5">
      <h2 className="mb-3 text-lg font-bold">Мой гараж</h2>
      {error && <p className="text-sm text-red-600">{error}</p>}
      {vehicles === null && !error && <div className="h-12 animate-pulse rounded-lg bg-background" />}

      {vehicles && (
        <div className="space-y-2">
          {vehicles.map((v) => (
            <div key={v.id} className="flex flex-wrap items-center gap-3 rounded-lg border border-border px-4 py-3">
              <div className="min-w-0 flex-1">
                <div className="font-semibold">{v.name ?? v.modification.title}</div>
                <div className="text-sm text-muted">
                  {v.name && `${v.modification.title} · `}
                  {v.vin ? <span className="article">{v.vin}</span> : "VIN не указан"}
                </div>
              </div>
              <button type="button" className="btn" onClick={() => pick(v)}>
                Подобрать запчасти
              </button>
              <button
                type="button"
                className="btn-ghost"
                onClick={() => remove(v.id)}
                aria-label={`Удалить ${v.modification.title} из гаража`}
              >
                Удалить
              </button>
            </div>
          ))}

          {selected && !selectedSaved && (
            <button type="button" className="btn-ghost w-full" onClick={() => add(selected.id)}>
              + Добавить {selected.title}
            </button>
          )}
          {vehicles.length === 0 && !selected && (
            <p className="text-sm text-muted">
              Выберите автомобиль{" "}
              <Link href="/" className="text-accent hover:underline">
                на главной
              </Link>{" "}
              и нажмите «В гараж» — тогда запчасти можно будет подбирать в один клик.
            </p>
          )}
        </div>
      )}
    </section>
  );
}

function Orders() {
  const [orders, setOrders] = useState<Order[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    authApi.orders
      .list()
      .then((r) => setOrders(r.data))
      .catch((e) => handleUnauthorized(e) || setError("Не удалось загрузить заказы."));
  }, []);

  return (
    <section className="card p-5">
      <h2 className="mb-3 text-lg font-bold">Заказы</h2>
      {error && <p className="text-sm text-red-600">{error}</p>}
      {orders === null && !error && <div className="h-12 animate-pulse rounded-lg bg-background" />}
      {orders?.length === 0 && (
        <p className="text-sm text-muted">
          Заказов пока нет. Заказы, оформленные после входа в кабинет, появятся здесь.
        </p>
      )}
      {orders && orders.length > 0 && (
        <div className="divide-y divide-border">
          {orders.map((o) => (
            <Link
              key={o.number}
              href={`/order/${o.number}?phone=${encodeURIComponent(o.contact_phone)}`}
              className="flex flex-wrap items-center gap-x-4 gap-y-1 py-3 hover:text-accent"
            >
              <span className="article">№ {o.number}</span>
              <span className="text-sm text-muted">{new Date(o.created_at).toLocaleDateString("ru-RU")}</span>
              <span className="rounded-lg bg-accent/10 px-2 py-0.5 text-xs font-semibold text-accent">
                {o.status.label}
              </span>
              <span className="ml-auto font-semibold tabular-nums">{formatPrice(o.total)}</span>
            </Link>
          ))}
        </div>
      )}
    </section>
  );
}

function Profile() {
  const session = useSession();
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [message, setMessage] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  if (!session) return null;

  async function save(form: FormData) {
    setSaving(true);
    setErrors({});
    setMessage(null);
    const password = String(form.get("password") ?? "");

    try {
      const customer = await authApi.auth.update({
        name: String(form.get("name")),
        phone: String(form.get("phone") ?? "") || null,
        marketing_consent: form.get("marketing_consent") === "1",
        ...(password ? { password, current_password: String(form.get("current_password") ?? "") } : {}),
      });
      updateCustomer(customer);
      setMessage("Сохранено.");
    } catch (e) {
      if (e instanceof ApiError && e.status === 422) setErrors(e.errors);
      else if (!handleUnauthorized(e)) setMessage("Не удалось сохранить.");
    }
    setSaving(false);
  }

  return (
    <section className="card p-5">
      <h2 className="mb-3 text-lg font-bold">Профиль</h2>
      <form action={save} className="grid gap-3 sm:grid-cols-2">
        <Field label="Имя" name="name" errors={errors} required defaultValue={session.customer.name} />
        <Field
          label="Телефон"
          name="phone"
          errors={errors}
          type="tel"
          autoComplete="tel"
          defaultValue={session.customer.phone ?? ""}
        />
        <Field
          label="Текущий пароль"
          name="current_password"
          errors={errors}
          type="password"
          autoComplete="current-password"
        />
        <Field
          label="Новый пароль"
          name="password"
          errors={errors}
          type="password"
          minLength={8}
          autoComplete="new-password"
        />
        <label className="flex items-center gap-3 text-sm sm:col-span-2">
          <input
            type="checkbox"
            name="marketing_consent"
            value="1"
            defaultChecked={session.customer.marketing_consent}
            className="size-4 accent-[var(--accent)]"
          />
          <span>
            Получать новости и скидки (<DocLink slug="marketing-consent">согласие на рассылки</DocLink>). Снимите
            галочку, чтобы отписаться.
          </span>
        </label>
        <div className="flex items-center gap-3 sm:col-span-2">
          <button type="submit" className="btn" disabled={saving}>
            {saving ? "Сохраняем…" : "Сохранить"}
          </button>
          {message && <span className="text-sm text-muted">{message}</span>}
        </div>
      </form>
    </section>
  );
}

/**
 * Удаление аккаунта = отзыв согласия на обработку персональных данных (152-ФЗ, ст. 9).
 * Заказы магазин хранит по закону о бухучёте, но они больше не связаны с аккаунтом.
 */
function DeleteAccount() {
  const router = useRouter();
  const [open, setOpen] = useState(false);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [deleting, setDeleting] = useState(false);

  async function remove(form: FormData) {
    setDeleting(true);
    setErrors({});
    try {
      await authApi.auth.deleteAccount(String(form.get("password") ?? ""));
      setSession(null);
      router.replace("/");
    } catch (e) {
      if (e instanceof ApiError && e.status === 422) setErrors(e.errors);
      else if (!handleUnauthorized(e)) setErrors({ password: ["Не удалось удалить аккаунт. Попробуйте позже."] });
      setDeleting(false);
    }
  }

  return (
    <section className="card p-5">
      <h2 className="mb-2 text-lg font-bold">Удаление аккаунта</h2>
      <p className="text-sm text-muted">
        Удалим профиль, гараж и вход в кабинет — это одновременно отзыв согласия на обработку персональных данных.
        Сведения о прошлых заказах магазин обязан хранить для бухгалтерского учёта, но они больше не будут связаны с
        аккаунтом.
      </p>
      {open ? (
        <form action={remove} className="mt-3 flex flex-wrap items-end gap-3">
          <div className="min-w-56 flex-1">
            <Field
              label="Пароль для подтверждения"
              name="password"
              errors={errors}
              type="password"
              required
              autoComplete="current-password"
            />
          </div>
          <button type="submit" className="btn bg-red-600 hover:bg-red-700" disabled={deleting}>
            {deleting ? "Удаляем…" : "Удалить навсегда"}
          </button>
          <button type="button" className="btn-ghost" onClick={() => setOpen(false)}>
            Отмена
          </button>
        </form>
      ) : (
        <button type="button" className="btn-ghost mt-3 text-red-600" onClick={() => setOpen(true)}>
          Удалить аккаунт
        </button>
      )}
    </section>
  );
}

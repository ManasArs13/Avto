"use client";

import { ApiError } from "@avto/shared";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { Field } from "@/components/field";
import { authApi, setSession } from "@/lib/auth";

type Mode = "login" | "register";

export default function LoginPage() {
  const router = useRouter();
  const [mode, setMode] = useState<Mode>("login");
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [message, setMessage] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function submit(form: FormData) {
    setSubmitting(true);
    setErrors({});
    setMessage(null);
    const value = (name: string) => String(form.get(name) ?? "");

    try {
      const session =
        mode === "login"
          ? await authApi.auth.login({ email: value("email"), password: value("password"), device_name: "web" })
          : await authApi.auth.register({
              name: value("name"),
              email: value("email"),
              phone: value("phone") || undefined,
              password: value("password"),
              device_name: "web",
            });
      setSession(session);
      router.replace("/account");
    } catch (e) {
      if (e instanceof ApiError && e.status === 422) {
        setErrors(e.errors);
      } else if (e instanceof ApiError && e.status === 429) {
        setMessage("Слишком много попыток. Подождите минуту.");
      } else {
        setMessage("Не удалось связаться с сервером. Попробуйте ещё раз.");
      }
      setSubmitting(false);
    }
  }

  function switchMode(next: Mode) {
    setMode(next);
    setErrors({});
    setMessage(null);
  }

  return (
    <div className="card mx-auto max-w-md p-6 sm:p-8">
      <div className="mb-6 grid grid-cols-2 gap-1 rounded-lg bg-background p-1 text-sm font-medium">
        {(["login", "register"] as const).map((m) => (
          <button
            key={m}
            type="button"
            onClick={() => switchMode(m)}
            className={`rounded-md py-2 ${mode === m ? "bg-surface shadow-sm" : "text-muted"}`}
            aria-pressed={mode === m}
          >
            {m === "login" ? "Вход" : "Регистрация"}
          </button>
        ))}
      </div>

      <form action={submit} className="space-y-3">
        {mode === "register" && <Field label="Имя" name="name" errors={errors} required autoComplete="name" />}
        <Field label="Email" name="email" errors={errors} required type="email" autoComplete="email" />
        {mode === "register" && <Field label="Телефон" name="phone" errors={errors} type="tel" autoComplete="tel" />}
        <Field
          label="Пароль"
          name="password"
          errors={errors}
          required
          type="password"
          minLength={mode === "register" ? 8 : undefined}
          autoComplete={mode === "login" ? "current-password" : "new-password"}
        />
        {message && <p className="text-sm text-red-600">{message}</p>}
        <button type="submit" className="btn w-full py-3" disabled={submitting}>
          {submitting ? "Подождите…" : mode === "login" ? "Войти" : "Зарегистрироваться"}
        </button>
      </form>

      <p className="mt-4 text-center text-sm text-muted">
        В кабинете — история заказов и ваши автомобили для быстрого подбора запчастей.
      </p>
    </div>
  );
}

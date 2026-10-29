"use client";

import type { CarMake, CarModel, CarModification } from "@avto/shared";
import { useState } from "react";
import { api } from "@/lib/api";
import { authApi, handleUnauthorized, useSession } from "@/lib/auth";
import { setSelectedCar, useSelectedCar } from "@/lib/car";

/** Сохранить выбранную машину в «Мой гараж» (только для вошедших покупателей). */
function SaveToGarage({ modificationId }: { modificationId: number }) {
  const [state, setState] = useState<{ id: number; status: "saving" | "saved" | "error" } | null>(null);
  // Сменили машину — кнопка снова активна.
  const status = state?.id === modificationId ? state.status : null;

  async function save() {
    setState({ id: modificationId, status: "saving" });
    try {
      await authApi.garage.add({ car_modification_id: modificationId });
      setState({ id: modificationId, status: "saved" });
    } catch (e) {
      handleUnauthorized(e);
      setState({ id: modificationId, status: "error" });
    }
  }

  if (status === "saved") return <span className="text-sm text-success">В гараже ✓</span>;

  return (
    <button type="button" className="btn-ghost" onClick={save} disabled={status === "saving"}>
      {status === "error" ? "Не удалось, ещё раз" : "В гараж"}
    </button>
  );
}

/**
 * Марка → модель → модификация. Марки приходят с сервера (кэшируются),
 * модели и модификации подгружаются в браузере по выбору.
 * Если марка одна (магазин LADA), модели приходят сразу, а выбор марки скрыт.
 */
export function VehicleSelector({ makes, initialModels = [] }: { makes: CarMake[]; initialModels?: CarModel[] }) {
  const singleMake = makes.length === 1;
  const selected = useSelectedCar();
  const session = useSession();
  const [models, setModels] = useState<CarModel[]>(initialModels);
  const [modifications, setModifications] = useState<CarModification[]>([]);
  const [makeId, setMakeId] = useState(singleMake ? String(makes[0].id) : "");
  const [modelId, setModelId] = useState("");
  const [loading, setLoading] = useState(false);

  async function onMake(id: string) {
    setMakeId(id);
    setModelId("");
    setModels([]);
    setModifications([]);
    if (!id) return;
    setLoading(true);
    setModels(await api.vehicles.models(Number(id)).finally(() => setLoading(false)));
  }

  async function onModel(id: string) {
    setModelId(id);
    setModifications([]);
    if (!id) return;
    setLoading(true);
    setModifications(await api.vehicles.modifications(Number(id)).finally(() => setLoading(false)));
  }

  function onModification(id: string) {
    const mod = modifications.find((m) => m.id === Number(id));
    const make = makes.find((m) => m.id === Number(makeId));
    const model = models.find((m) => m.id === Number(modelId));
    if (mod && make && model) {
      setSelectedCar({ id: mod.id, title: `${make.name} ${model.name} ${mod.name}` });
    }
  }

  if (selected) {
    return (
      <div className="flex flex-wrap items-center gap-3">
        <div className="min-w-0 flex-1">
          <div className="text-xs text-muted">Ваш автомобиль</div>
          <div className="font-semibold">{selected.title}</div>
        </div>
        {session && <SaveToGarage modificationId={selected.id} />}
        <button type="button" className="btn-ghost" onClick={() => setSelectedCar(null)}>
          Сменить
        </button>
      </div>
    );
  }

  return (
    <div className={`grid gap-2 ${singleMake ? "sm:grid-cols-2" : "sm:grid-cols-3"}`}>
      {!singleMake && (
        <select className="input" value={makeId} onChange={(e) => onMake(e.target.value)} aria-label="Марка">
          <option value="">Марка</option>
          {makes.map((m) => (
            <option key={m.id} value={m.id}>
              {m.name}
            </option>
          ))}
        </select>
      )}
      <select
        className="input"
        value={modelId}
        onChange={(e) => onModel(e.target.value)}
        disabled={!models.length}
        aria-label="Модель"
      >
        <option value="">{loading && !models.length ? "Загрузка…" : "Модель"}</option>
        {models.map((m) => (
          <option key={m.id} value={m.id}>
            {m.name} {m.year_from ? `(${m.year_from}–${m.year_to ?? "н.в."})` : ""}
          </option>
        ))}
      </select>
      <select
        className="input"
        defaultValue=""
        onChange={(e) => onModification(e.target.value)}
        disabled={!modifications.length}
        aria-label="Модификация"
      >
        <option value="">{loading && modelId ? "Загрузка…" : "Двигатель"}</option>
        {modifications.map((m) => (
          <option key={m.id} value={m.id}>
            {m.name}
            {m.engine_code ? ` · ${m.engine_code}` : ""}
          </option>
        ))}
      </select>
    </div>
  );
}

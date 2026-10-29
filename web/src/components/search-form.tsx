import Form from "next/form";

export function SearchForm({ defaultValue, large }: { defaultValue?: string; large?: boolean }) {
  return (
    <Form action="/search" className="flex gap-2">
      <input
        name="q"
        defaultValue={defaultValue}
        placeholder="Артикул или название, например 04465-33450"
        aria-label="Поиск запчастей"
        required
        minLength={2}
        className={`input ${large ? "py-3 text-base" : ""}`}
      />
      <button type="submit" className={`btn ${large ? "px-6" : ""}`}>
        Найти
      </button>
    </Form>
  );
}

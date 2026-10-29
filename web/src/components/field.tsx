/** Поле формы с ошибкой валидации Laravel ({ "поле": ["сообщение"] }). */
export function Field({
  label,
  name,
  errors,
  ...props
}: { label: string; name: string; errors: Record<string, string[]> } & React.InputHTMLAttributes<HTMLInputElement>) {
  const error = errors[name]?.[0];
  return (
    <label className="block">
      <span className="mb-1 block text-sm font-medium">{label}</span>
      <input name={name} className={`input ${error ? "border-red-500" : ""}`} {...props} />
      {error && <span className="mt-1 block text-xs text-red-600">{error}</span>}
    </label>
  );
}

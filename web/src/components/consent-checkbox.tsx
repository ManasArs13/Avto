import Link from "next/link";
import type { ReactNode } from "react";

/**
 * Галочка согласия. По 152-ФЗ согласие должно быть конкретным и сознательным,
 * поэтому по умолчанию она не отмечена, а текст документа доступен по ссылке.
 */
export function ConsentCheckbox({
  name,
  required,
  error,
  children,
}: {
  name: string;
  required?: boolean;
  error?: string;
  children: ReactNode;
}) {
  return (
    <label className="flex items-start gap-3 text-sm">
      <input
        type="checkbox"
        name={name}
        value="1"
        required={required}
        className="mt-0.5 size-4 shrink-0 accent-[var(--accent)]"
      />
      <span>
        {children}
        {error && <span className="mt-1 block text-xs text-red-600">{error}</span>}
      </span>
    </label>
  );
}

export function DocLink({ slug, children }: { slug: string; children: ReactNode }) {
  return (
    <Link href={`/legal/${slug}`} target="_blank" className="text-accent hover:underline">
      {children}
    </Link>
  );
}

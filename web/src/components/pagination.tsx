import Link from "next/link";

export function Pagination({ page, lastPage, href }: { page: number; lastPage: number; href: (page: number) => string }) {
  if (lastPage <= 1) return null;

  return (
    <div className="flex justify-center gap-2">
      {page > 1 && (
        <Link href={href(page - 1)} className="btn-ghost">
          ← Назад
        </Link>
      )}
      <span className="px-3 py-2 text-sm text-muted">
        {page} из {lastPage}
      </span>
      {page < lastPage && (
        <Link href={href(page + 1)} className="btn-ghost">
          Вперёд →
        </Link>
      )}
    </div>
  );
}

/** ?page=2 → 2; всё остальное → 1 */
export function parsePage(value: string | string[] | undefined): number {
  return typeof value === "string" && /^\d+$/.test(value) && Number(value) > 0 ? Number(value) : 1;
}

import Link from "next/link";
import { connection } from "next/server";
import { Suspense } from "react";
import { getShopInfo } from "@/lib/data";

/**
 * Подвал с реквизитами продавца и ссылками на документы.
 * При дистанционной продаже покупатель должен видеть наименование, ОГРН, адрес и контакты продавца
 * (ЗоЗПП ст. 26.1, правила продажи № 2463).
 */
export function SiteFooter() {
  return (
    <footer className="border-t border-border py-8 text-sm text-muted">
      <div className="mx-auto max-w-6xl px-4">
        <Suspense fallback={<div className="h-24" />}>
          <FooterContent />
        </Suspense>
      </div>
    </footer>
  );
}

async function FooterContent() {
  await connection();
  const shop = await getShopInfo().catch(() => null);
  if (!shop) return <p>Avto · оригинальные запчасти ВАЗ</p>;

  const d = shop.details;

  return (
    <div className="grid gap-6 sm:grid-cols-[1fr_auto]">
      <div className="space-y-1">
        <p className="font-semibold text-foreground">{d.shop_name || "Avto"} · оригинальные запчасти ВАЗ</p>
        {d.legal_name && (
          <p>
            {d.legal_name}
            {d.inn && `, ИНН ${d.inn}`}
            {d.ogrn && `, ${d.ogrn.length === 15 ? "ОГРНИП" : "ОГРН"} ${d.ogrn}`}
          </p>
        )}
        {d.legal_address && <p>{d.legal_address}</p>}
        <p className="flex flex-wrap gap-x-4">
          {d.phone && (
            <a href={`tel:${d.phone.replace(/[^\d+]/g, "")}`} className="hover:text-accent">
              {d.phone}
            </a>
          )}
          {d.email && (
            <a href={`mailto:${d.email}`} className="hover:text-accent">
              {d.email}
            </a>
          )}
          {d.work_hours && <span>{d.work_hours}</span>}
        </p>
      </div>
      <ul className="space-y-1 sm:text-right">
        {shop.documents.map((doc) => (
          <li key={doc.slug}>
            <Link href={`/legal/${doc.slug}`} className="hover:text-accent">
              {doc.title}
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
}

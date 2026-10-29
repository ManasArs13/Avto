import { formatPrice } from "@avto/shared";
import Link from "next/link";
import { notFound } from "next/navigation";
import { Suspense } from "react";
import { OffersTable } from "@/components/offers-table";
import { getProduct } from "@/lib/data";

export async function generateMetadata(props: PageProps<"/product/[id]">) {
  const { id } = await props.params;
  const data = await getProduct(Number(id));
  if (!data) return { title: "Товар не найден" };
  const { product } = data;
  return {
    title: `${product.brand?.name} ${product.article} — ${product.name}`,
    description: `${product.name} ${product.brand?.name} ${product.article}: цена, наличие и аналоги.`,
  };
}

export default function ProductPage(props: PageProps<"/product/[id]">) {
  return (
    <Suspense fallback={<div className="card h-64 animate-pulse" />}>
      <ProductDetails params={props.params} />
    </Suspense>
  );
}

async function ProductDetails({ params }: Pick<PageProps<"/product/[id]">, "params">) {
  const { id } = await params;
  const data = Number.isInteger(Number(id)) ? await getProduct(Number(id)) : null;
  if (!data) notFound();

  const { product, analogs } = data;
  const specs = Object.entries(product.specs ?? {});

  return (
    <div className="space-y-8">
      <nav className="text-sm text-muted">
        <Link href="/" className="hover:text-accent">
          Главная
        </Link>
        {product.category && (
          <>
            {" / "}
            <Link href={`/catalog/${product.category.slug}`} className="hover:text-accent">
              {product.category.name}
            </Link>
          </>
        )}
      </nav>

      <section className="card overflow-hidden">
        <div className="border-b border-border p-5">
          <div className="flex flex-wrap items-baseline gap-3">
            <span className="text-lg font-bold">{product.brand?.name}</span>
            {product.brand?.is_oem && (
              <span className="rounded bg-accent/10 px-2 py-0.5 text-xs font-semibold text-accent">оригинал</span>
            )}
          </div>
          <h1 className="mt-1 text-2xl font-bold sm:text-3xl">
            <span className="article">{product.article}</span>
          </h1>
          <p className="mt-1 text-muted">{product.name}</p>
          {product.description && <p className="mt-3 max-w-3xl text-sm">{product.description}</p>}
          {specs.length > 0 && (
            <dl className="mt-4 grid max-w-xl grid-cols-2 gap-x-6 gap-y-1 text-sm">
              {specs.map(([k, v]) => (
                <div key={k} className="contents">
                  <dt className="text-muted">{k}</dt>
                  <dd>{v}</dd>
                </div>
              ))}
            </dl>
          )}
        </div>
        <OffersTable product={product} offers={product.offers} />
      </section>

      {analogs.length > 0 && (
        <section className="space-y-3">
          <h2 className="text-xl font-bold">Аналоги ({analogs.length})</h2>
          {analogs.map((analog) => (
            <div key={analog.id} className="card overflow-hidden">
              <Link
                href={`/product/${analog.id}`}
                className="flex flex-wrap items-baseline gap-x-3 border-b border-border px-4 py-3 hover:text-accent"
              >
                <span className="font-bold">{analog.brand?.name}</span>
                <span className="article">{analog.article}</span>
                <span className="text-sm text-muted">{analog.name}</span>
                <span className="ml-auto text-sm text-muted">от {formatPrice(analog.offers[0]?.price)}</span>
              </Link>
              <OffersTable product={analog} offers={analog.offers.slice(0, 3)} />
            </div>
          ))}
        </section>
      )}
    </div>
  );
}

import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Suspense } from "react";
import { Breadcrumbs } from "@/components/breadcrumbs";
import { getLegalDocument } from "@/lib/data";

type Props = PageProps<"/legal/[slug]">;

export async function generateMetadata(props: Props): Promise<Metadata> {
  const { slug } = await props.params;
  const doc = await getLegalDocument(slug);
  return doc
    ? { title: doc.title, alternates: { canonical: `/legal/${slug}` } }
    : { title: "Документ не найден" };
}

export default function LegalPage(props: Props) {
  return (
    <Suspense fallback={<div className="card h-96 animate-pulse" />}>
      <LegalDocumentView params={props.params} />
    </Suspense>
  );
}

async function LegalDocumentView({ params }: Pick<Props, "params">) {
  const { slug } = await params;
  const doc = await getLegalDocument(slug);
  if (!doc) notFound();

  return (
    <article className="mx-auto max-w-3xl space-y-4">
      <Breadcrumbs items={[{ name: doc.title }]} />
      <h1 className="text-2xl font-bold sm:text-3xl">{doc.title}</h1>
      <p className="text-sm text-muted">
        Редакция {doc.version}
        {doc.updated_at && ` от ${new Date(doc.updated_at).toLocaleDateString("ru-RU")}`}
      </p>
      {/* HTML собран на сервере из Markdown, сырой HTML в тексте экранирован (LegalDocument::html). */}
      <div className="legal card p-5 sm:p-8" dangerouslySetInnerHTML={{ __html: doc.html }} />
    </article>
  );
}

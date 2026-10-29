import type { Metadata } from "next";
import { Inter } from "next/font/google";
import Link from "next/link";
import { CartLink } from "@/components/cart-link";
import { SearchForm } from "@/components/search-form";
import "./globals.css";

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin", "cyrillic"],
});

export const metadata: Metadata = {
  title: {
    default: "Avto — автозапчасти по артикулу и VIN",
    template: "%s — Avto",
  },
  description: "Оригинальные запчасти и аналоги с доставкой. Поиск по артикулу и подбор по автомобилю.",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="ru" className={`${inter.variable} h-full antialiased`}>
      <body className="flex min-h-full flex-col font-sans">
        <header className="sticky top-0 z-10 border-b border-border bg-surface/90 backdrop-blur">
          <div className="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3 sm:gap-6">
            <Link href="/" className="shrink-0 text-xl font-black tracking-tight">
              Av<span className="text-accent">to</span>
            </Link>
            <div className="min-w-0 flex-1">
              <SearchForm />
            </div>
            <CartLink />
          </div>
        </header>

        <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:py-8">{children}</main>

        <footer className="border-t border-border py-6 text-center text-sm text-muted">
          Avto · демо-витрина · данные каталога условные
        </footer>
      </body>
    </html>
  );
}

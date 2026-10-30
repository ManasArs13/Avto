import type { Metadata, Viewport } from "next";
import { Inter } from "next/font/google";
import Link from "next/link";
import { AccountLink } from "@/components/account-link";
import { CartLink } from "@/components/cart-link";
import { CookieNotice } from "@/components/cookie-notice";
import { Logo } from "@/components/logo";
import { SearchForm } from "@/components/search-form";
import { SiteFooter } from "@/components/site-footer";
import { SITE_URL } from "@/lib/site";
import "./globals.css";

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin", "cyrillic"],
});

export const metadata: Metadata = {
  metadataBase: new URL(SITE_URL),
  title: {
    default: "Avto — оригинальные запчасти ВАЗ (LADA)",
    template: "%s — Avto",
  },
  description:
    "Оригинальные запчасти ВАЗ (LADA) с доставкой. Поиск по каталожному номеру и подбор по модели и двигателю.",
  applicationName: "Avto",
};

export const viewport: Viewport = {
  themeColor: [
    { media: "(prefers-color-scheme: light)", color: "#ffffff" },
    { media: "(prefers-color-scheme: dark)", color: "#18181b" },
  ],
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="ru" className={`${inter.variable} h-full antialiased`}>
      <body className="flex min-h-full flex-col font-sans">
        <header className="sticky top-0 z-10 border-b border-border bg-surface/90 backdrop-blur">
          <div className="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3 sm:gap-6">
            <Logo />
            <div className="min-w-0 flex-1">
              <SearchForm />
            </div>
            <Link href="/cars" className="hidden text-sm font-medium text-muted hover:text-accent sm:block">
              Модели
            </Link>
            <AccountLink />
            <CartLink />
          </div>
        </header>

        <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:py-8">{children}</main>

        <SiteFooter />
        <CookieNotice />
      </body>
    </html>
  );
}

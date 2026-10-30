import type { Metadata } from "next";

// Кабинет целиком клиентский и зависит от входа покупателя — мгновенную навигацию для него не проверяем.
export const instant = false;

export const metadata: Metadata = {
  title: "Личный кабинет",
  robots: { index: false, follow: false },
};

export default function AccountLayout({ children }: LayoutProps<"/account">) {
  return <div className="mx-auto max-w-4xl">{children}</div>;
}

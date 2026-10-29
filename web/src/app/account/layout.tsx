import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Личный кабинет",
  robots: { index: false, follow: false },
};

export default function AccountLayout({ children }: LayoutProps<"/account">) {
  return <div className="mx-auto max-w-4xl">{children}</div>;
}

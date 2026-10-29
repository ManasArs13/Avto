import Link from "next/link";

/** Знак: шестигранная гайка с «А». Исходники и PNG — в /brand. */
export function LogoMark({ className = "h-9 w-9" }: { className?: string }) {
  return (
    <svg viewBox="0 0 64 64" className={className} aria-hidden>
      <polygon
        points="32,4.5 55.8,18.25 55.8,45.75 32,59.5 8.2,45.75 8.2,18.25"
        fill="var(--accent)"
        stroke="var(--accent)"
        strokeWidth="5"
        strokeLinejoin="round"
      />
      <path
        d="M20.5 46 L32 18.5 L43.5 46"
        fill="none"
        stroke="#fff"
        strokeWidth="7"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
      <path d="M26 37.5 H38" stroke="#fff" strokeWidth="5.5" strokeLinecap="round" />
    </svg>
  );
}

export function Logo() {
  return (
    <Link href="/" className="flex shrink-0 items-center gap-2" aria-label="Avto — запчасти ВАЗ, на главную">
      <LogoMark />
      <span className="hidden flex-col leading-none sm:flex">
        <span className="text-xl font-black tracking-tight">Avto</span>
        <span className="mt-0.5 text-[10px] font-semibold tracking-[0.18em] text-muted">ЗАПЧАСТИ ВАЗ</span>
      </span>
    </Link>
  );
}

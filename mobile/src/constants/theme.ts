// Цвета из фирменного стиля (brand/README.md) — те же, что на сайте.
export const Colors = {
  light: {
    text: '#18181B',
    textSecondary: '#71717A',
    background: '#F6F6F4',
    surface: '#FFFFFF',
    border: '#E4E4E7',
    accent: '#EA580C',
    success: '#16A34A',
    danger: '#DC2626',
  },
  dark: {
    text: '#F4F4F5',
    textSecondary: '#A1A1AA',
    background: '#0C0C0D',
    surface: '#18181B',
    border: '#2E2E33',
    accent: '#F97316',
    success: '#22C55E',
    danger: '#F87171',
  },
} as const;

export type Theme = (typeof Colors)['light' | 'dark'];

export const Spacing = {
  one: 4,
  two: 8,
  three: 16,
  four: 24,
} as const;

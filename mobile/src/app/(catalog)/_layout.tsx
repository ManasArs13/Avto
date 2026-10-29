import { Stack } from 'expo-router';

import { useTheme } from '@/hooks/use-theme';

export default function CatalogLayout() {
  const theme = useTheme();

  return (
    <Stack screenOptions={{ headerTintColor: theme.accent, headerLargeTitle: true }}>
      <Stack.Screen name="index" options={{ title: 'Запчасти ВАЗ' }} />
      <Stack.Screen name="product/[id]" options={{ title: '', headerLargeTitle: false }} />
    </Stack>
  );
}

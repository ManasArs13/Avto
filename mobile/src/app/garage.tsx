import type { GarageVehicle } from '@avto/shared';
import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';

import { Button, Card, Label, Loading, Screen, Title } from '@/components/ui';
import { api } from '@/lib/api';
import { useSession } from '@/lib/session';

/** «Мой гараж» — тот же список, что в кабинете на сайте. */
export default function GarageScreen() {
  const session = useSession();
  const [vehicles, setVehicles] = useState<GarageVehicle[] | null>(null);

  // Обновляем при каждом открытии вкладки: машину могли добавить на сайте.
  useFocusEffect(
    useCallback(() => {
      if (session) api.garage.list().then(setVehicles, () => setVehicles([]));
    }, [session]),
  );

  if (!session) {
    return (
      <Screen>
        <Title>Мой гараж</Title>
        <Label muted>Войдите во вкладке «Профиль», чтобы сохранять свои автомобили и подбирать запчасти к ним.</Label>
      </Screen>
    );
  }

  return (
    <Screen>
      <Title>Мой гараж</Title>
      {vehicles === null && <Loading />}
      {vehicles?.length === 0 && (
        <Label muted>Пока пусто. Добавьте автомобиль на сайте в подборе по машине — он появится здесь.</Label>
      )}
      {vehicles?.map((v) => (
        <Card key={v.id}>
          <Label style={{ fontWeight: '700' }}>{v.name ?? v.modification.title}</Label>
          {v.name && <Label muted>{v.modification.title}</Label>}
          <Label muted>{v.vin ? `VIN ${v.vin}` : 'VIN не указан'}</Label>
          <Button
            variant="ghost"
            title="Удалить"
            onPress={async () => {
              await api.garage.remove(v.id);
              setVehicles((list) => list?.filter((x) => x.id !== v.id) ?? null);
            }}
          />
        </Card>
      ))}
    </Screen>
  );
}

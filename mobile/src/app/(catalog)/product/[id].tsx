import { formatDelivery, formatPrice, type Offer, type Product, type ProductDetails } from '@avto/shared';
import { Stack, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { View } from 'react-native';

import { Button, Card, Label, Loading, Screen, Title } from '@/components/ui';
import { useTheme } from '@/hooks/use-theme';
import { api } from '@/lib/api';
import { cart } from '@/lib/cart';

export default function ProductScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const [data, setData] = useState<ProductDetails | null>(null);
  const [error, setError] = useState(false);

  useEffect(() => {
    api.catalog.product(Number(id)).then(setData, () => setError(true));
  }, [id]);

  if (error) {
    return (
      <Screen>
        <Label muted>Товар не найден или нет связи с сервером.</Label>
      </Screen>
    );
  }
  if (!data) return <Loading />;

  const { product, analogs, fits } = data;

  return (
    <Screen>
      <Stack.Screen options={{ title: product.article }} />
      <View style={{ gap: 4 }}>
        <Label style={{ fontWeight: '700' }}>{product.brand?.name}</Label>
        <Title>{product.article}</Title>
        <Label muted>{product.name}</Label>
        {fits.length > 0 && (
          <Label muted>Подходит для: {fits.map((f) => `${f.make.name} ${f.model.name}`).join(', ')}</Label>
        )}
      </View>

      <Offers product={product} offers={product.offers} />

      {analogs.length > 0 && <Title style={{ fontSize: 20 }}>Аналоги</Title>}
      {analogs.map((analog) => (
        <View key={analog.id} style={{ gap: 8 }}>
          <Label style={{ fontWeight: '700' }}>
            {analog.brand?.name} {analog.article}
          </Label>
          <Offers product={analog} offers={analog.offers.slice(0, 3)} />
        </View>
      ))}
    </Screen>
  );
}

function Offers({ product, offers }: { product: Product; offers: Offer[] }) {
  const theme = useTheme();
  const [added, setAdded] = useState<number | null>(null);

  if (offers.length === 0) return <Label muted>Нет в наличии</Label>;

  return offers.map((offer) => (
    <Card key={offer.id} style={{ flexDirection: 'row', alignItems: 'center' }}>
      <View style={{ flex: 1, gap: 2 }}>
        <Label style={{ fontSize: 18, fontWeight: '700' }}>{formatPrice(offer.price)}</Label>
        <Label muted>
          {offer.in_stock ? 'В магазине' : formatDelivery(offer.delivery_days)} · {offer.quantity} шт.
        </Label>
      </View>
      {added === offer.id ? (
        <Label style={{ color: theme.success, fontWeight: '600' }}>В корзине ✓</Label>
      ) : (
        <Button
          title="В корзину"
          onPress={() => {
            cart.add(product, offer);
            setAdded(offer.id);
          }}
        />
      )}
    </Card>
  ));
}

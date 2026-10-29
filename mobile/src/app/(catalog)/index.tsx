import { formatPrice, type Product } from '@avto/shared';
import { Link } from 'expo-router';
import { useState } from 'react';
import { Pressable, View } from 'react-native';

import { Card, Input, Label, Loading, Screen } from '@/components/ui';
import { useTheme } from '@/hooks/use-theme';
import { api } from '@/lib/api';

const EXAMPLES = ['2108-3501080', '21080101200508', 'ремень ГРМ'];

/** Поиск по каталожному номеру ВАЗ (в любом формате) или по названию. */
export default function SearchScreen() {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<Product[] | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function search(q = query) {
    if (q.trim().length < 2) return;
    setQuery(q);
    setLoading(true);
    setError(null);
    try {
      setResults(await api.catalog.search(q.trim()));
    } catch {
      setError('Не удалось выполнить поиск. Проверьте подключение.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <Screen>
      <Input
        value={query}
        onChangeText={setQuery}
        onSubmitEditing={() => search()}
        placeholder="Номер детали или название"
        returnKeyType="search"
        autoCapitalize="characters"
        autoCorrect={false}
      />
      {results === null && (
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 12 }}>
          <Label muted>Например:</Label>
          {EXAMPLES.map((q) => (
            <Label key={q} onPress={() => search(q)} style={{ fontWeight: '600' }}>
              {q}
            </Label>
          ))}
        </View>
      )}
      {loading && <Loading />}
      {error && <Label muted>{error}</Label>}
      {!loading && results?.length === 0 && <Label muted>Ничего не нашли. Проверьте номер.</Label>}
      {!loading && results?.map((p) => <ProductRow key={p.id} product={p} />)}
    </Screen>
  );
}

function ProductRow({ product }: { product: Product }) {
  const theme = useTheme();
  return (
    <Link href={{ pathname: '/product/[id]', params: { id: product.id } }} asChild>
      <Pressable>
        <Card>
          <Label style={{ fontWeight: '700' }}>
            {product.brand?.name}
            {product.brand?.is_oem ? <Label style={{ color: theme.accent }}> · оригинал</Label> : null}
          </Label>
          <Label style={{ fontFamily: 'monospace', fontWeight: '600' }}>{product.article}</Label>
          <Label muted>{product.name}</Label>
          {(product.offers_count ?? 0) > 0 ? (
            <Label style={{ fontWeight: '700' }}>от {formatPrice(product.min_price)}</Label>
          ) : (
            <Label muted>нет в наличии</Label>
          )}
        </Card>
      </Pressable>
    </Link>
  );
}

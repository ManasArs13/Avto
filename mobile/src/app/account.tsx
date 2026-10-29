import { ApiError, formatPrice, type Order } from '@avto/shared';
import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { View } from 'react-native';

import { Button, Card, Input, Label, Screen, Title } from '@/components/ui';
import { api } from '@/lib/api';
import { setSession, useSession } from '@/lib/session';

export default function AccountScreen() {
  const session = useSession();
  return session ? <Profile /> : <Login />;
}

function Login() {
  const [mode, setMode] = useState<'login' | 'register'>('login');
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [message, setMessage] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function submit() {
    setLoading(true);
    setErrors({});
    setMessage(null);
    try {
      const session =
        mode === 'login'
          ? await api.auth.login({ email, password, device_name: 'mobile' })
          : await api.auth.register({ name, email, password, device_name: 'mobile' });
      await setSession(session);
    } catch (e) {
      if (e instanceof ApiError && e.status === 422) setErrors(e.errors);
      else setMessage('Не удалось связаться с сервером.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <Screen>
      <Title>{mode === 'login' ? 'Вход' : 'Регистрация'}</Title>
      {mode === 'register' && <Input label="Имя" value={name} onChangeText={setName} error={errors.name?.[0]} />}
      <Input
        label="Email"
        value={email}
        onChangeText={setEmail}
        keyboardType="email-address"
        autoCapitalize="none"
        autoComplete="email"
        error={errors.email?.[0]}
      />
      <Input
        label="Пароль"
        value={password}
        onChangeText={setPassword}
        secureTextEntry
        autoComplete={mode === 'login' ? 'current-password' : 'new-password'}
        error={errors.password?.[0]}
      />
      {message && <Label muted>{message}</Label>}
      <Button title={mode === 'login' ? 'Войти' : 'Зарегистрироваться'} onPress={submit} loading={loading} />
      <Button
        variant="ghost"
        title={mode === 'login' ? 'Нет аккаунта? Регистрация' : 'Уже есть аккаунт? Вход'}
        onPress={() => setMode(mode === 'login' ? 'register' : 'login')}
      />
    </Screen>
  );
}

function Profile() {
  const session = useSession();
  const [orders, setOrders] = useState<Order[] | null>(null);

  useFocusEffect(
    useCallback(() => {
      api.orders.list().then(
        (r) => setOrders(r.data),
        async (e) => {
          // Токен отозван (например, вышли на всех устройствах) — выходим.
          if (e instanceof ApiError && e.status === 401) await setSession(null);
        },
      );
    }, []),
  );

  if (!session) return null;

  return (
    <Screen>
      <View>
        <Title>{session.customer.name}</Title>
        <Label muted>{session.customer.email}</Label>
      </View>

      <Title style={{ fontSize: 20 }}>Заказы</Title>
      {orders?.length === 0 && <Label muted>Заказов пока нет.</Label>}
      {orders?.map((o) => (
        <Card key={o.number} style={{ flexDirection: 'row', alignItems: 'center' }}>
          <View style={{ flex: 1 }}>
            <Label style={{ fontFamily: 'monospace', fontWeight: '600' }}>№ {o.number}</Label>
            <Label muted>{o.status.label}</Label>
          </View>
          <Label style={{ fontWeight: '700' }}>{formatPrice(o.total)}</Label>
        </Card>
      ))}

      <Button
        variant="ghost"
        title="Выйти"
        onPress={async () => {
          await api.auth.logout().catch(() => undefined);
          await setSession(null);
        }}
      />
    </Screen>
  );
}

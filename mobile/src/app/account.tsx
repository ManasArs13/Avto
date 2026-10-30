import { ApiError, formatPrice, type Order } from '@avto/shared';
import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { Alert, View } from 'react-native';

import { Button, Card, Checkbox, DocLink, Input, Label, Screen, Title } from '@/components/ui';
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
  const [consent, setConsent] = useState(false);
  const [marketing, setMarketing] = useState(false);
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
          : await api.auth.register({
              name,
              email,
              password,
              device_name: 'mobile',
              consent_personal_data: consent,
              consent_marketing: marketing,
            });
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
      {mode === 'register' && (
        <>
          <Checkbox checked={consent} onChange={setConsent} error={errors.consent_personal_data?.[0]}>
            Даю <DocLink slug="personal-data-consent">согласие на обработку персональных данных</DocLink> и подтверждаю,
            что ознакомлен(а) с <DocLink slug="privacy">Политикой обработки персональных данных</DocLink>
          </Checkbox>
          <Checkbox checked={marketing} onChange={setMarketing}>
            Хочу получать новости и скидки (<DocLink slug="marketing-consent">согласие на рассылки</DocLink>) —
            необязательно
          </Checkbox>
        </>
      )}
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

      <DeleteAccount />

      <View style={{ gap: 6 }}>
        <DocLink slug="privacy">Политика обработки персональных данных</DocLink>
        <DocLink slug="offer">Публичная оферта</DocLink>
        <DocLink slug="returns">Возврат и обмен</DocLink>
      </View>
    </Screen>
  );
}

/**
 * Удаление аккаунта прямо в приложении — требование App Store и Google Play,
 * а по 152-ФЗ это ещё и отзыв согласия на обработку персональных данных.
 */
function DeleteAccount() {
  const [open, setOpen] = useState(false);
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | undefined>();
  const [loading, setLoading] = useState(false);

  async function remove() {
    setLoading(true);
    setError(undefined);
    try {
      await api.auth.deleteAccount(password);
      await setSession(null);
    } catch (e) {
      setError(e instanceof ApiError && e.status === 422 ? e.errors.password?.[0] : 'Не удалось удалить аккаунт.');
      setLoading(false);
    }
  }

  if (!open) return <Button variant="ghost" title="Удалить аккаунт" onPress={() => setOpen(true)} />;

  return (
    <Card>
      <Label muted>
        Удалим профиль и гараж — это отзыв согласия на обработку персональных данных. Сведения о заказах магазин хранит
        для бухгалтерского учёта, но без связи с аккаунтом.
      </Label>
      <Input label="Пароль" value={password} onChangeText={setPassword} secureTextEntry error={error} />
      <Button
        title="Удалить навсегда"
        onPress={() =>
          Alert.alert('Удалить аккаунт?', 'Восстановить его будет нельзя.', [
            { text: 'Отмена', style: 'cancel' },
            { text: 'Удалить', style: 'destructive', onPress: remove },
          ])
        }
        loading={loading}
        disabled={!password}
      />
      <Button variant="ghost" title="Отмена" onPress={() => setOpen(false)} />
    </Card>
  );
}

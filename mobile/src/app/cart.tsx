import { ApiError, formatPrice } from '@avto/shared';
import { useState } from 'react';
import { Alert, View } from 'react-native';

import { Button, Card, Checkbox, DocLink, Input, Label, Screen, Title } from '@/components/ui';
import { api } from '@/lib/api';
import { cart, useCart } from '@/lib/cart';
import { useSession } from '@/lib/session';

/** Корзина и оформление. В каркасе — самовывоз и оплата при получении; остальное — как на сайте. */
export default function CartScreen() {
  const { items, total } = useCart();
  const customer = useSession()?.customer;
  const [name, setName] = useState(customer?.name ?? '');
  const [phone, setPhone] = useState(customer?.phone ?? '');
  const [consent, setConsent] = useState(false);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [submitting, setSubmitting] = useState(false);

  async function submit() {
    setSubmitting(true);
    setErrors({});
    try {
      const order = await api.orders.create({
        items: items.map((i) => ({ offer_id: i.offerId, quantity: i.quantity })),
        contact_name: name,
        contact_phone: phone,
        contact_email: customer?.email,
        delivery_method: 'pickup',
        payment_method: 'on_receipt',
        // Покупатель с аккаунтом дал согласие при регистрации; гость — здесь.
        ...(customer ? {} : { consent_personal_data: consent }),
      });
      cart.clear();
      Alert.alert(`Заказ № ${order.number} оформлен`, 'Мы позвоним для подтверждения.');
    } catch (e) {
      if (e instanceof ApiError && e.status === 422) setErrors(e.errors);
      else Alert.alert('Не удалось оформить заказ', 'Проверьте подключение и попробуйте ещё раз.');
    } finally {
      setSubmitting(false);
    }
  }

  if (items.length === 0) {
    return (
      <Screen>
        <Title>Корзина</Title>
        <Label muted>Пока пусто. Найдите деталь по номеру во вкладке «Каталог».</Label>
      </Screen>
    );
  }

  const itemErrors = Object.entries(errors).filter(([k]) => k.startsWith('items.'));

  return (
    <Screen>
      <Title>Корзина</Title>
      {items.map((item) => (
        <Card key={item.offerId}>
          <Label style={{ fontWeight: '700' }}>
            {item.brand} {item.article}
          </Label>
          <Label muted>{item.name}</Label>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 12 }}>
            <Button variant="ghost" title="−" onPress={() => cart.setQuantity(item.offerId, item.quantity - 1)} />
            <Label>{item.quantity}</Label>
            <Button variant="ghost" title="+" onPress={() => cart.setQuantity(item.offerId, item.quantity + 1)} />
            <Label style={{ marginLeft: 'auto', fontWeight: '700' }}>{formatPrice(item.price * item.quantity)}</Label>
          </View>
        </Card>
      ))}

      <Card>
        <Input label="Имя" value={name} onChangeText={setName} autoComplete="name" error={errors.contact_name?.[0]} />
        <Input
          label="Телефон"
          value={phone}
          onChangeText={setPhone}
          keyboardType="phone-pad"
          autoComplete="tel"
          error={errors.contact_phone?.[0]}
        />
        <Label muted>Самовывоз из магазина, оплата при получении.</Label>
        {!customer && (
          <Checkbox checked={consent} onChange={setConsent} error={errors.consent_personal_data?.[0]}>
            Даю <DocLink slug="personal-data-consent">согласие на обработку персональных данных</DocLink>
          </Checkbox>
        )}
        <Label muted style={{ fontSize: 13 }}>
          Оформляя заказ, вы принимаете условия <DocLink slug="offer">публичной оферты</DocLink> и{' '}
          <DocLink slug="returns">возврата</DocLink>.
        </Label>
      </Card>

      {itemErrors.map(([k, v]) => (
        <Label key={k} muted>
          {v.join(' ')}
        </Label>
      ))}
      <Button title={`Оформить · ${formatPrice(total)}`} onPress={submit} loading={submitting} />
    </Screen>
  );
}

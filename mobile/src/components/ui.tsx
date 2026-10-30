import type { ReactNode } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
  type PressableProps,
  type TextInputProps,
  type TextProps,
  type ViewProps,
} from 'react-native';

import { Spacing } from '@/constants/theme';
import { openDocument } from '@/lib/api';
import { useTheme } from '@/hooks/use-theme';

// Минимальный набор элементов интерфейса в фирменных цветах.

export function Screen({ children }: { children: ReactNode }) {
  const theme = useTheme();
  return (
    <ScrollView
      style={{ backgroundColor: theme.background }}
      contentContainerStyle={styles.screen}
      contentInsetAdjustmentBehavior="automatic"
      keyboardShouldPersistTaps="handled">
      {children}
    </ScrollView>
  );
}

export function Title({ style, ...props }: TextProps) {
  const theme = useTheme();
  return <Text style={[styles.title, { color: theme.text }, style]} {...props} />;
}

export function Label({ muted, style, ...props }: TextProps & { muted?: boolean }) {
  const theme = useTheme();
  return <Text style={[styles.label, { color: muted ? theme.textSecondary : theme.text }, style]} {...props} />;
}

export function Card({ style, ...props }: ViewProps) {
  const theme = useTheme();
  return (
    <View style={[styles.card, { backgroundColor: theme.surface, borderColor: theme.border }, style]} {...props} />
  );
}

export function Button({
  title,
  variant = 'primary',
  loading,
  disabled,
  ...props
}: PressableProps & { title: string; variant?: 'primary' | 'ghost'; loading?: boolean }) {
  const theme = useTheme();
  const primary = variant === 'primary';
  return (
    <Pressable
      accessibilityRole="button"
      disabled={disabled || loading}
      style={({ pressed }) => [
        styles.button,
        primary
          ? { backgroundColor: theme.accent }
          : { borderColor: theme.border, borderWidth: StyleSheet.hairlineWidth * 2 },
        (pressed || disabled) && { opacity: 0.6 },
      ]}
      {...props}>
      {loading ? (
        <ActivityIndicator color={primary ? '#fff' : theme.accent} />
      ) : (
        <Text style={[styles.buttonText, { color: primary ? '#fff' : theme.text }]}>{title}</Text>
      )}
    </Pressable>
  );
}

export function Input({ label, error, ...props }: TextInputProps & { label?: string; error?: string }) {
  const theme = useTheme();
  return (
    <View style={{ gap: Spacing.one }}>
      {label && <Label>{label}</Label>}
      <TextInput
        placeholderTextColor={theme.textSecondary}
        style={[
          styles.input,
          { color: theme.text, backgroundColor: theme.surface, borderColor: error ? theme.danger : theme.border },
        ]}
        {...props}
      />
      {error && <Text style={{ color: theme.danger, fontSize: 13 }}>{error}</Text>}
    </View>
  );
}

export function Loading() {
  const theme = useTheme();
  return <ActivityIndicator style={{ marginTop: Spacing.four }} color={theme.accent} />;
}

const styles = StyleSheet.create({
  screen: { padding: Spacing.three, gap: Spacing.three },
  title: { fontSize: 24, fontWeight: '800' },
  label: { fontSize: 15 },
  card: { borderRadius: 12, borderWidth: StyleSheet.hairlineWidth * 2, padding: Spacing.three, gap: Spacing.two },
  button: { borderRadius: 10, paddingVertical: 12, paddingHorizontal: Spacing.three, alignItems: 'center' },
  buttonText: { fontSize: 15, fontWeight: '600' },
  checkbox: { width: 22, height: 22, borderRadius: 6, borderWidth: 2, alignItems: 'center', justifyContent: 'center', marginTop: 1 },
  input: { borderRadius: 10, borderWidth: StyleSheet.hairlineWidth * 2, paddingHorizontal: 12, paddingVertical: 10, fontSize: 16 },
});

/** Галочка согласия: по умолчанию не отмечена (152-ФЗ). */
export function Checkbox({
  checked,
  onChange,
  children,
  error,
}: {
  checked: boolean;
  onChange: (value: boolean) => void;
  children: ReactNode;
  error?: string;
}) {
  const theme = useTheme();
  return (
    <Pressable
      accessibilityRole="checkbox"
      accessibilityState={{ checked }}
      onPress={() => onChange(!checked)}
      style={{ flexDirection: 'row', gap: Spacing.two, alignItems: 'flex-start' }}>
      <View
        style={[
          styles.checkbox,
          { borderColor: error ? theme.danger : theme.border },
          checked && { backgroundColor: theme.accent, borderColor: theme.accent },
        ]}>
        {checked && <Text style={{ color: '#fff', fontSize: 14, fontWeight: '800' }}>✓</Text>}
      </View>
      <View style={{ flex: 1, gap: 2 }}>
        <Text style={{ color: theme.text, fontSize: 14, lineHeight: 20 }}>{children}</Text>
        {error && <Text style={{ color: theme.danger, fontSize: 13 }}>{error}</Text>}
      </View>
    </Pressable>
  );
}

/** Ссылка на документ на сайте (открывается во встроенном браузере). */
export function DocLink({ slug, children }: { slug: string; children: ReactNode }) {
  const theme = useTheme();
  return (
    <Text style={{ color: theme.accent }} onPress={() => openDocument(slug)}>
      {children}
    </Text>
  );
}

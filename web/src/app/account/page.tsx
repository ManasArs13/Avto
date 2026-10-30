import { AccountView } from "./account-view";

// Кабинет рисуется в браузере по токену покупателя — мгновенную навигацию для него не проверяем.
export const instant = false;

export default function AccountPage() {
  return <AccountView />;
}

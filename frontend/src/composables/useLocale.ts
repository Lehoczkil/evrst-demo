import { useI18n } from 'vue-i18n';
import type { Language } from '@/translations';

const STORAGE_KEY = 'evrst:language';

export const useLocale = () => {
  const { locale } = useI18n({ useScope: 'global' });

  const setLocale = (next: Language) => {
    locale.value = next;
    if (typeof window !== 'undefined') {
      window.localStorage.setItem(STORAGE_KEY, next);
      document.documentElement.lang = next;
      window.dispatchEvent(new CustomEvent('locale-changed', { detail: next }));
    }
  };

  return { locale, setLocale };
};

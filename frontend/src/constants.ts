import type { TranslationKey } from '@/i18n';

export const MENU_ITEMS: {
  labelKey: TranslationKey;
  url: string;
}[] = [
  { labelKey: 'nav.events', url: '/#events' },
  { labelKey: 'nav.about', url: '/#about' },
  { labelKey: 'nav.team', url: '/#team' },
  { labelKey: 'nav.mentors', url: '/#mentors' },
  { labelKey: 'nav.sponsors', url: '/#sponsors' },
  { labelKey: 'nav.joinUs', url: '/join-us' },
];

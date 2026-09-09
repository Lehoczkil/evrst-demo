import * as Sentry from '@sentry/vue';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import { createI18n } from 'vue-i18n';
import { MotionPlugin } from 'motion-v';

import { localeFromPath } from '@/composables/useLanguage';
import App from '@/app/App.vue';
import router from '@/router';
import translations, { type Language } from '@/translations';

import 'virtual:uno.css';
import '@unocss/reset/tailwind-compat.css';

const STORAGE_KEY = 'evrst:language';

/*
  Hungarian by default: this is a Hungarian university team writing for a
  Hungarian audience, and the site opened in English until now.

  Resolution order is path → stored choice → browser preference → hu.

  The PATH comes first, and that is deliberate: a shared /csatlakozz link
  was sent by someone who chose the language, and a stored preference from
  a previous visit should not silently override what they shared. `/` is
  spelled the same in both locales, so it carries no signal and falls
  through.
*/
const initialLocale = ((): Language => {
  if (typeof window === 'undefined') return 'hu';

  const fromPath = localeFromPath(window.location.pathname);
  if (fromPath) {
    return fromPath;
  }

  const stored = window.localStorage.getItem(STORAGE_KEY);
  if (stored === 'hu' || stored === 'en') {
    return stored;
  }

  return window.navigator.language?.toLowerCase().startsWith('hu') ? 'hu' : 'en';
})();

/*
  index.html ships `<html lang="hu">` as a static default, but the resolved
  locale can be `en` — and a document whose lang attribute disagrees with
  its content misleads screen readers, browser translation and search
  indexing alike. `useLocale().setLocale` keeps it in step on every later
  change; this is the boot case it could not cover.
*/
if (typeof document !== 'undefined') {
  document.documentElement.lang = initialLocale;
}

const i18n = createI18n({
  locale: initialLocale,
  legacy: false,
  globalInjection: true,
  fallbackLocale: 'hu',
  messages: translations,
  warnHtmlMessage: false,
  missingWarn: false,
  fallbackWarn: false,
});

const app = createApp(App);
app
  .use(router)
  .use(createPinia())
  .use(i18n)
  .use(MotionPlugin);

if (import.meta.env.VITE_SENTRY_DSN && import.meta.env.VITE_ENV && import.meta.env.VITE_ENV !== 'develop') {
  Sentry.init({
    app,
    dsn: import.meta.env.VITE_SENTRY_DSN,
    environment: import.meta.env.VITE_ENV,
    release: import.meta.env.VITE_COMMIT_HASH?.substring?.(0, 8),
    integrations: [
      Sentry.breadcrumbsIntegration(),
      Sentry.dedupeIntegration(),
      Sentry.functionToStringIntegration(),
      Sentry.globalHandlersIntegration(),
      Sentry.httpContextIntegration(),
      Sentry.inboundFiltersIntegration(),
      Sentry.linkedErrorsIntegration(),
      Sentry.vueIntegration(),
    ],
    ignoreErrors: [
      'TypeError: Failed to fetch',
      'TypeError: Load failed',
      'ResizeObserver loop limit exceeded',
      'Unable to preload CSS',
    ],
    tracesSampleRate: 0,
  });
}

app.mount('#vue');

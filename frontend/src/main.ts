import * as Sentry from '@sentry/vue';
import { createPinia } from 'pinia';
import { createApp } from 'vue';
import { createI18n } from 'vue-i18n';
import { MotionPlugin } from 'motion-v';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import Aura from '@primevue/themes/aura';
import { definePreset } from '@primevue/themes';

import App from '@/app/App.vue';
import router from '@/router';
import translations, { type Language } from '@/translations';

import 'virtual:uno.css';
import '@unocss/reset/tailwind-compat.css';

const STORAGE_KEY = 'evrst:language';

const initialLocale = ((): Language => {
  if (typeof window === 'undefined') return 'en';
  const stored = window.localStorage.getItem(STORAGE_KEY);
  return stored === 'hu' || stored === 'en' ? stored : 'en';
})();

const i18n = createI18n({
  locale: initialLocale,
  legacy: false,
  globalInjection: true,
  fallbackLocale: 'en',
  messages: translations,
  warnHtmlMessage: false,
  missingWarn: false,
  fallbackWarn: false,
});

const app = createApp(App);
const EvrstPreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: '#fdf6ec',
      100: '#fae5c1',
      200: '#f6d496',
      300: '#f3c46a',
      400: '#f2b853',
      500: '#f2ac3c',
      600: '#d99935',
      700: '#a8772a',
      800: '#77541e',
      900: '#473213',
      950: '#251a0a',
    },
  },
});

app
  .use(router)
  .use(createPinia())
  .use(i18n)
  .use(PrimeVue, {
    theme: {
      preset: EvrstPreset,
      options: {
        darkModeSelector: 'system',
      },
    },
  })
  .use(ToastService)
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

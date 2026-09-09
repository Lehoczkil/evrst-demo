import type { RouteRecordRaw } from 'vue-router';
import { pathsFor } from '@/composables/useLanguage';

/*
  One record per named route PER LOCALE, so both spellings resolve and the
  catch-all never sees them.

  Order matters: the named routes are registered first, and `/:slug(.*)*`
  last. A localized path landing on the CMS loader instead of its own page
  would look like a missing page rather than a routing bug.
*/
type Loader = () => Promise<unknown>;

const localized = (name: string, component: Loader): RouteRecordRaw[] =>
  pathsFor(name).map((path, i) => ({
    path,
    // Vue Router requires unique names, so only the first spelling takes
    // the bare name; the rest are suffixed. Nothing navigates by name —
    // useLanguage resolves paths — so the suffix is never referenced.
    name: i === 0 ? name : `${name}:${i}`,
    component,
  } as RouteRecordRaw));

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    component: () => import('@/pages/HomePage.vue'),
  },
  ...localized('joinUs', () => import('@/pages/JoinUsPage.vue')),
  {
    path: '/:slug(.*)*',
    component: () => import('@/pages/DynamicPage.vue'),
  },
];

export default routes;

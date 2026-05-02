import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@/pages/HomePage.vue'),
  },
  {
    path: '/join-us',
    component: () => import('@/pages/JoinUsPage.vue'),
  },
  {
    path: '/:slug(.*)*',
    component: () => import('@/pages/DynamicPage.vue'),
  },
];

export default routes;

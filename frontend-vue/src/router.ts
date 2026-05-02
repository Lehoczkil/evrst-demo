import { createRouter, createWebHistory } from 'vue-router';
import routes from './routes';

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: (to, _from, savedPosition) => {
    if (to.hash) {
      return new Promise((resolve) => {
        setTimeout(() => {
          const el = document.getElementById(to.hash.slice(1));
          if (el) {
            const top = el.getBoundingClientRect().top + window.scrollY - 80;
            resolve({ top, behavior: 'smooth' });
          } else {
            resolve({ top: 0 });
          }
        }, 50);
      });
    }
    if (savedPosition) return savedPosition;
    return { top: 0, behavior: 'smooth' };
  },
});

export default router;

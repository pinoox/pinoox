import { createRouter, createWebHistory } from 'vue-router';
import { routes } from './routes';
import { authGuard } from './guards';

const url = import.meta.env.MODE === 'production' ? PINOOX.URL.BASE : import.meta.env.BASE_URL;

const router = createRouter({
    history: createWebHistory(url),
    routes: routes,

    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        } else {
            return { top: 0 };
        }
    }
});

router.beforeEach(authGuard);
export default router;
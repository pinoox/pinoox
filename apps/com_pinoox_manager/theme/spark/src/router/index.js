import { createRouter, createWebHistory } from 'vue-router';
import { routes } from './routes';
import { authGuard } from './guards';

import { getUrl } from '@/boot.js';

const base = getUrl().BASE || import.meta.env.BASE_URL;

const router = createRouter({
    history: createWebHistory(base),
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
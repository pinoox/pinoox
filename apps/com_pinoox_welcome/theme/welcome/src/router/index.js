import { createRouter, createWebHistory } from 'vue-router';
import PageHome from '../views/pages/page-home.vue';
import { getUrl } from '../boot.js';

const base = getUrl().BASE || import.meta.env.BASE_URL;

const router = createRouter({
    history: createWebHistory(base),
    routes: [
        {
            path: '/',
            name: 'page-home',
            component: PageHome,
        },
    ],
});

export default router;

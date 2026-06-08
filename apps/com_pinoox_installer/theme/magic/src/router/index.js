import { createRouter, createWebHistory } from 'vue-router';
import { routes } from './routes';
import { getUrl } from '@/boot.js';

const base = import.meta.env.MODE === 'production' ? getUrl().BASE : import.meta.env.BASE_URL;

export default createRouter({
    history: createWebHistory(base),
    routes,
});

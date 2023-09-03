import {createRouter, createWebHistory} from 'vue-router'
import PageHome from '../views/pages/page-home.vue'

const url = import.meta.env.MODE === 'production' ? PINOOX.URL.BASE : import.meta.env.BASE_URL;

const router = createRouter({
    history: createWebHistory(url),
    routes: [
        {
            path: '/',
            name: 'page-home',
            component: PageHome
        },
    ]
})

export default router

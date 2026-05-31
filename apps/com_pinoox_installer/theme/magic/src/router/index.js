import {createRouter, createWebHistory} from 'vue-router'
import {routes} from './routes'

const url = import.meta.env.MODE === 'production' ? PINOOX.URL.BASE : import.meta.env.BASE_URL;
export default createRouter({
    history: createWebHistory(url),
    routes,
})

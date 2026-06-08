import { createApp, createSSRApp } from 'vue';
import { createPinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import App from './App.vue';
import { createRouter } from './router/index.js';
import messages from './assets/js/i18n.js';
import { getBoot } from './boot.js';

export function createAppFactory(options = {}) {
    const { ssr = false, routerBase = '/' } = options;
    const boot = getBoot();
    const create = ssr ? createSSRApp : createApp;
    const app = create(App);
    const router = createRouter(ssr, routerBase);

    app.use(createPinia());
    app.use(router);

    const i18n = createI18n({
        locale: boot.locale || 'fa',
        legacy: false,
        messages,
    });

    app.use(i18n);

    return { app, router, i18n };
}

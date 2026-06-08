import { renderToString } from 'vue/server-renderer';
import { createAppFactory } from './createApp.js';

export async function render(url, boot, routerBase = '/') {
    globalThis.__PINOOX__ = boot;

    const { app, router } = createAppFactory({ ssr: true, routerBase });
    await router.push(url);
    await router.isReady();

    return renderToString(app);
}

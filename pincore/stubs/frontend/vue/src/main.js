import { createApp } from 'vue';
import App from './App.vue';

const mount = document.querySelector(window.__PINOOX_MOUNT__ || '#app');
if (mount) {
    createApp(App).mount(mount);
}

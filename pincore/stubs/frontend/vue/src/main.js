import './bootstrap.dev.js';
import { createApp } from 'vue';
import App from './App.vue';
import { getBoot } from './boot.js';

const boot = getBoot();
const html = document.documentElement;

if (boot.locale && html.lang !== boot.locale) {
    html.lang = boot.locale;
}

if (boot.direction) {
    html.dir = boot.direction;
}

createApp(App).mount('#app');

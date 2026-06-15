import './assets/js/fonts.js';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import VueSvgInlinePlugin from 'vue-svg-inline-plugin';
import 'vue-svg-inline-plugin/src/polyfills';
import App from './App.vue';
import router from './router';
import './assets/scss/style.scss';
import CodeBlock from '@wdns/vue-code-block';
import { createI18n } from 'vue-i18n';
import messages from './assets/js/i18n';
import { getBoot } from './boot.js';

const boot = getBoot();
const html = document.documentElement;

if (boot.locale && html.lang !== boot.locale) {
    html.lang = boot.locale;
}

if (boot.direction) {
    html.dir = boot.direction;
    document.body.classList.add(boot.direction);
}

const app = createApp(App);

app.use(VueSvgInlinePlugin, {
    attributes: {
        data: ['src'],
        remove: ['alt'],
    },
});
app.use(createPinia());
app.use(router);
app.component('CodeBlock', CodeBlock);

const i18n = createI18n({
    locale: boot.locale || document.documentElement.lang,
    legacy: false,
    messages,
});

app.use(i18n);
app.config.globalProperties.$t = i18n.global.t;
app.config.globalProperties.$tm = i18n.global.tm;
app.config.globalProperties.$te = i18n.global.te;
app.config.globalProperties.$n = i18n.global.n;
app.config.globalProperties.$rt = i18n.global.rt;
app.config.globalProperties.$d = i18n.global.d;

app.mount('#app');

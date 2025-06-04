import { createApp } from "vue";
import { createPinia } from "pinia";
import VueSvgInlinePlugin from "vue-svg-inline-plugin";
import "vue-svg-inline-plugin/src/polyfills";
import App from "./App.vue";
import router from "./router";
import "./assets/scss/style.scss";
import CodeBlock from "vue3-code-block";

const app = createApp(App);
app.use(VueSvgInlinePlugin);
app.use(createPinia());
app.use(router);
app.component("CodeBlock", CodeBlock);
app.use(VueSvgInlinePlugin, {
  attributes: {
    data: ["src"],
    remove: ["alt"]
  }
});

//---------------------------- Localization ----------------------------
import {createI18n} from 'vue-i18n';
import messages from "./assets/js/i18n";

const i18n = createI18n({
  locale: document.documentElement.lang,
  legacy: false,
  resolve: (locale, key, options) => {
    const translation = i18n.messages[locale][key];
    if (typeof translation === 'object') {
      return translation;
    }
    return translation;
  },
  messages,
})
app.use(i18n);
app.config.globalProperties.$t = i18n.global.t;
app.config.globalProperties.$tm = i18n.global.tm;
app.config.globalProperties.$te = i18n.global.te;
app.config.globalProperties.$n = i18n.global.n;
app.config.globalProperties.$rt = i18n.global.rt;
app.config.globalProperties.$d = i18n.global.d;
app.config.globalProperties._url = PINOOX.URL;

//---------------------------- Mount ----------------------------
app.mount("#app");

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
app.mount("#app");

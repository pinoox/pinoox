import { createApp } from "vue";
import App from "./App.vue";
import axios from "axios";
import VueAxios from "vue-axios";
import store from "@/stores";
import router from "./router";
import "@/assets/styles/tailwind-config.css";
import "@/assets/styles/main.scss";

const app = createApp(App);

// ---------------------------- Plugins ----------------------------
app.use(VueAxios, axios);
app.use(store);
app.use(router);

//---------------------------- Mixin ----------------------------

import {saxIcon, mdiIcon} from '@/const/icons.js';

app.mixin({data: () => ({saxIcon, mdiIcon})});


// ---------------------------- Mount ----------------------------
app.mount("#app");
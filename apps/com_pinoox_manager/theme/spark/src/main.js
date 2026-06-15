import { createApp } from "vue";
import 'dockbar';
import App from "./App.vue";
import axios from "axios";
import VueAxios from "vue-axios";
import store from "@/stores";
import router from "./router";
import "@/assets/styles/tailwind-config.css";
import "@/assets/styles/main.scss";
import { createModal } from '@kolirt/vue-modal'
import Notifications, { notify } from '@kyvg/vue3-notification';
import { bindNotify } from '@utils/helpers/toastHelper.js';

const app = createApp(App);

// ---------------------------- Plugins ----------------------------
app.use(VueAxios, axios);
app.use(store);
app.use(router);
app.use(Notifications);
bindNotify(notify);
app.use(createModal({
    groups: {
        default: {
            disableCloseOnInteractOutside: true,
        },
    },
}))

//---------------------------- Mixin ----------------------------

import {saxIcon, mdiIcon} from '@/const/icons.js';

app.mixin({data: () => ({saxIcon, mdiIcon})});


// ---------------------------- Mount ----------------------------
app.mount("#app");

import { createApp } from "vue";
import App from "./App.vue";
import axios from "axios";
import VueAxios from "vue-axios";
import store from "@/stores";
import router from "./router";
import "@/assets/styles/tailwind-config.css";
import "@/assets/styles/main.scss";
import { createModal } from '@kolirt/vue-modal'

const app = createApp(App);

// ---------------------------- Plugins ----------------------------
app.use(VueAxios, axios);
app.use(store);
app.use(router);
app.use(createModal({
    transitionTime: 200,
    animationType: 'fadeIn',
    modalStyle: {
        'background-color': 'rgba(0,0,0,0.1)',
        padding: '2rem 2rem',
        align: 'center',
        'z-index': 201,
    },
    overlayStyle: {
        'background-color': 'rgba(0,0,0,0.1)',
        'backdrop-filter': 'blur(15px)',
        'z-index': 200
    }
}))

//---------------------------- Mixin ----------------------------

import {saxIcon, mdiIcon} from '@/const/icons.js';

app.mixin({data: () => ({saxIcon, mdiIcon})});


// ---------------------------- Mount ----------------------------
app.mount("#app");
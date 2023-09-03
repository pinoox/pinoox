import Vue from "vue";
import VueRouter from "vue-router";
import {routes} from "./routes";

Vue.use(VueRouter);

// router
export default new VueRouter({
    mode: 'history',
    routes: routes,
    props:true
});
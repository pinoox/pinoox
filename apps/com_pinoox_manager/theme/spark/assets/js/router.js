import Vue from "vue";
import VueRouter from "vue-router";
import {routes} from "./routes";

Vue.use(VueRouter);

// router
const router = new VueRouter({
    mode: 'history',
    routes: routes
});

router.beforeEach((to, from, next) => {
    var user = JSON.parse(localStorage.getItem('pinoox_auth'));
    if (to.meta.requireAuth !== undefined) {
        if (to.meta.requireAuth) {
            if (user == null || !user.isLogin) {
                next({name: 'market-login'});
            } else {
                next();
            }
        } else {
            if (user == null || !user.isLogin) {
                next();
            } else {
                next({name: 'market-account'});
            }
        }

    } else {
        next();
    }
});

export default router;
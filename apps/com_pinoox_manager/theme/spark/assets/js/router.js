import Vue from "vue";
import VueRouter from "vue-router";
import {routes} from "./routes";
import Store from './store';

Vue.use(VueRouter);

// router
const router = new VueRouter({
    mode: 'history',
    routes: routes
});

router.beforeEach((to, from, next) => {
    let user = Store.state.pinooxAuth;
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
import {useAuthStore} from "../stores/modules/auth.js";

let isFirstSession = false;

export async function authGuard(to, from, next) {
    const store = useAuthStore();
    if (!isFirstSession) {
        isFirstSession = true;
        await store.canUserAccess();
    }
    const isAuth = store.isAuth;

    if (to.name !== 'login' && !isAuth) {
        next({ name: 'login' });
    } else if (to.name === 'login' && isAuth) {
        next({ name: 'desktop' });
    } else {
        next();
    }
}
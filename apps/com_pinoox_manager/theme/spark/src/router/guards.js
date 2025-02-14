import { useAuthStore } from "../stores/modules/auth.js";

export async function authGuard(to, from, next) {
    const store = useAuthStore();

    //await store.canUserAccess();

    const isLoginRequired = !!to?.meta?.loginRequired;
    const isAuth = store.isAuth;

    if (isLoginRequired && !isAuth) {
        next({ name: 'page-account-login' });
    } else if (to.name === 'page-account-login' && isAuth) {
        next({ name: 'page-profile-account' });
    } else {
        next();
    }
}
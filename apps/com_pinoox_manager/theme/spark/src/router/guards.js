import {useAuthStore} from "../stores/modules/auth.js";

let isFirstSession = false;

export async function authGuard(to, from, next) {
    const store = useAuthStore();
    if (!isFirstSession) {
        isFirstSession = true;
        await store.canUserAccess(true);
    }
    const isAuth = store.isAuth;

    if (to.name !== 'login' && !isAuth) {
        next({ name: 'login' });
    } else if (to.name === 'login' && isAuth) {
        const redirect = typeof to.query.redirect === 'string' ? to.query.redirect : '';

        if (redirect !== '' && redirect.startsWith('/') && !redirect.startsWith('//')) {
            window.location.assign(redirect);
            return;
        }

        next({ name: 'desktop' });
    } else {
        next();
    }
}
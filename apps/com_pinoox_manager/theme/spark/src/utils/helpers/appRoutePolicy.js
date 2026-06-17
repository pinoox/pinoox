export function resolveRouterMode(app) {
    if (app?.router_mode === 'single' || app?.router_mode === 'multiple') {
        return app.router_mode;
    }

    const router = app?.router;

    if (typeof router === 'string') {
        return router === 'single' ? 'single' : 'multiple';
    }

    if (router && typeof router === 'object') {
        const type = router.type ?? router.mode;

        if (type === 'single') {
            return 'single';
        }
    }

    return 'multiple';
}

export function canAssignAnotherRoute(app, routes = []) {
    if (resolveRouterMode(app) === 'multiple') {
        return true;
    }

    const packageName = app?.package_name ?? app?.package;

    if (!packageName) {
        return true;
    }

    return !routes.some((route) => route?.package === packageName);
}

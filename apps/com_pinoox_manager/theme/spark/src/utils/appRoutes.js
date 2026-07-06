export function normalizeAppRoutes(routes) {
    if (!routes) {
        return [];
    }

    if (Array.isArray(routes)) {
        return routes
            .map((entry) => {
                if (typeof entry === 'string') {
                    return {path: entry};
                }

                if (entry && typeof entry === 'object' && entry.path) {
                    return {path: String(entry.path)};
                }

                return null;
            })
            .filter(Boolean);
    }

    if (typeof routes === 'object') {
        return Object.keys(routes).map((path) => ({path: String(path)}));
    }

    return [];
}

export function listAppRoutePaths(routes, {excludeLocked = true} = {}) {
    return normalizeAppRoutes(routes)
        .map((route) => route.path)
        .filter((path) => !excludeLocked || (path !== '/manager' && path !== ''));
}

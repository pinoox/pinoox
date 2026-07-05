export function shouldSyncManagerBrowserUrl(windowStore) {
    return windowStore.mode === 'fullscreen';
}

export async function pushManagerBrowserRoute(router, windowStore, path) {
    const normalized = String(path ?? '').trim();

    if (!normalized || !shouldSyncManagerBrowserUrl(windowStore)) {
        return;
    }

    if (router.currentRoute.value.path !== normalized) {
        await router.push(normalized);
    }
}

export async function leaveManagerBrowserRoute(router, isRouteActive) {
    if (typeof isRouteActive === 'function' && isRouteActive(router.currentRoute.value)) {
        await router.push({name: 'desktop'});
    }
}

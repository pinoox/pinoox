import {useRouter} from 'vue-router';

export function useManagerWindowActions(options) {
    const {
        windowStore,
        isRouteActive,
        getPath,
        onClose,
        onMinimize,
        onToggleFloat,
    } = options;

    const router = useRouter();

    function resolvePath() {
        if (typeof getPath === 'function') {
            return getPath();
        }

        return windowStore.lastPath;
    }

    function leaveRouteIfNeeded() {
        if (isRouteActive(router.currentRoute.value)) {
            router.push({name: 'desktop'});
        }
    }

    function close() {
        windowStore.close();
        leaveRouteIfNeeded();
        onClose?.();
    }

    function minimize(isFloating) {
        const path = isRouteActive(router.currentRoute.value)
            ? router.currentRoute.value.path
            : resolvePath();

        windowStore.minimize(
            isFloating ? 'floating' : 'fullscreen',
            path,
        );
        leaveRouteIfNeeded();
        onMinimize?.();
    }

    function toggleFloat(isFloating) {
        if (isFloating) {
            windowStore.openFullscreen();
        } else {
            windowStore.enterFloating();
        }

        onToggleFloat?.(isFloating);
    }

    function focusWindow() {
        windowStore.focus();
    }

    return {
        close,
        minimize,
        toggleFloat,
        focusWindow,
    };
}

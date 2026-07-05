import {useRouter} from 'vue-router';
import {controlPanelAppPath} from '@/router/controlPanelPaths.js';
import {
    normalizeControlPanelPath,
    syncControlPanelMemoryRouter,
} from '@/router/controlPanelMemoryRouter.js';
import {useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';
import {useAppViewMode} from '@/views/composables/useAppViewMode.js';
import {pushManagerBrowserRoute} from '@/views/composables/useManagerWindowRouteSync.js';

export {controlPanelAppPath};

export function useControlPanelNavigation() {
    const router = useRouter();
    const controlPanelWindow = useControlPanelWindowStore();
    const {isAdvanced} = useAppViewMode();

    async function pushControlPath(path) {
        const normalized = normalizeControlPanelPath(path);
        controlPanelWindow.setLastPath(normalized);
        await syncControlPanelMemoryRouter(normalized);

        if (isAdvanced.value) {
            await pushManagerBrowserRoute(router, controlPanelWindow, normalized);
            return;
        }

        await router.push(normalized);
    }

    async function pushAppManager(packageName, section = 'details') {
        await pushControlPath(controlPanelAppPath(packageName, section));
    }

    function appManagerPath(packageName, section = 'details') {
        return normalizeControlPanelPath(controlPanelAppPath(packageName, section));
    }

    return {
        pushControlPath,
        pushAppManager,
        appManagerPath,
        controlPanelAppPath,
    };
}

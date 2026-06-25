import {useRouter} from 'vue-router';
import {controlPanelAppPath} from '@/router/controlPanelPaths.js';
import {
    normalizeControlPanelPath,
    syncControlPanelMemoryRouter,
    toMemoryRouterPath,
} from '@/router/controlPanelMemoryRouter.js';
import {useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';
import {isControlRoute} from '@/views/composables/useControlPanel.js';
import {useAppViewMode} from '@/views/composables/useAppViewMode.js';

export {controlPanelAppPath};

export function useControlPanelNavigation() {
    const router = useRouter();
    const controlPanelWindow = useControlPanelWindowStore();
    const {isAdvanced} = useAppViewMode();

    async function pushControlPath(path) {
        const normalized = normalizeControlPanelPath(path);
        controlPanelWindow.setLastPath(normalized);

        if (isAdvanced.value) {
            const memoryPath = toMemoryRouterPath(normalized);
            await syncControlPanelMemoryRouter(normalized);

            if (isControlRoute(router.currentRoute.value)) {
                if (router.currentRoute.value.path !== normalized) {
                    await router.push(normalized);
                }

                return;
            }

            if (router.currentRoute.value.path !== memoryPath) {
                await router.push(memoryPath);
            }

            return;
        }

        await router.push(normalized);
    }

    async function pushAppManager(packageName, section = 'details') {
        await pushControlPath(controlPanelAppPath(packageName, section));
    }

    function appManagerPath(packageName, section = 'details') {
        const normalized = normalizeControlPanelPath(controlPanelAppPath(packageName, section));

        if (isAdvanced.value && !isControlRoute(router.currentRoute.value)) {
            return toMemoryRouterPath(normalized);
        }

        return normalized;
    }

    return {
        pushControlPath,
        pushAppManager,
        appManagerPath,
        controlPanelAppPath,
    };
}

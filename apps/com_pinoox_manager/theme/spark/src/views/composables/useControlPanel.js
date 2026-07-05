import {useRouter} from 'vue-router';
import {CONTROL_PANEL_ID, useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';
import {useAppViewMode} from '@/views/composables/useAppViewMode.js';
import {syncControlPanelMemoryRouter} from '@/router/controlPanelMemoryRouter.js';
import {pushManagerBrowserRoute} from '@/views/composables/useManagerWindowRouteSync.js';

export {CONTROL_PANEL_ID};

export function isControlRoute(route) {
    return String(route?.path ?? '').startsWith('/control');
}

export function useControlPanel() {
    const router = useRouter();
    const {isAdvanced} = useAppViewMode();
    const controlPanelWindow = useControlPanelWindowStore();

    async function openControlPanel(path = '/control/apps') {
        controlPanelWindow.setLastPath(path);

        if (isAdvanced.value) {
            await syncControlPanelMemoryRouter(path);

            if (controlPanelWindow.isMinimized) {
                controlPanelWindow.restoreSession();
                await pushManagerBrowserRoute(router, controlPanelWindow, path);
                return;
            }

            if (!controlPanelWindow.isActive) {
                controlPanelWindow.openFullscreen();
            }

            await pushManagerBrowserRoute(router, controlPanelWindow, path);
            return;
        }

        await router.push(path);
    }

    function closeControlPanel() {
        if (isAdvanced.value && controlPanelWindow.isOpen) {
            controlPanelWindow.close();
        }

        if (isControlRoute(router.currentRoute.value)) {
            router.push({name: 'desktop'});
        }
    }

    return {
        openControlPanel,
        closeControlPanel,
        isControlRoute,
        controlPanelWindow,
    };
}

import {computed} from 'vue';
import {useRouteMeta} from '@/views/composables/useRouteMeta.js';
import {useAppViewMode} from '@/views/composables/useAppViewMode.js';
import {useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';

export function useManagerChrome() {
    const {hasToolbar: routeHasToolbar, isSingle, showDock} = useRouteMeta();
    const {isAdvanced} = useAppViewMode();
    const controlPanelWindow = useControlPanelWindowStore();

    const hasToolbar = computed(() => {
        if (routeHasToolbar.value) {
            return true;
        }

        return isAdvanced.value
            && controlPanelWindow.mode === 'floating'
            && controlPanelWindow.isVisible;
    });

    const showDockBar = computed(() => {
        if (showDock.value) {
            return true;
        }

        if (!isAdvanced.value) {
            return false;
        }

        return controlPanelWindow.mode === 'floating'
            || controlPanelWindow.mode === 'minimized';
    });

    return {
        hasToolbar,
        isSingle,
        showDockBar,
    };
}

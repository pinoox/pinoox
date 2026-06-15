import {computed} from 'vue';
import {useRoute} from 'vue-router';
import {useRouteMeta} from '@/views/composables/useRouteMeta.js';
import {useAppViewMode} from '@/views/composables/useAppViewMode.js';
import {isControlRoute} from '@/views/composables/useControlPanel.js';
import {useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';

export function useManagerChrome() {
    const route = useRoute();
    const {hasToolbar: routeHasToolbar, isSingle, showDock} = useRouteMeta();
    const {isAdvanced} = useAppViewMode();
    const controlPanelWindow = useControlPanelWindowStore();

    const hasToolbar = computed(() => {
        if (routeHasToolbar.value) {
            return true;
        }

        return isAdvanced.value
            && isControlRoute(route)
            && controlPanelWindow.mode === 'floating';
    });

    const showDockBar = computed(() => {
        if (showDock.value) {
            return true;
        }

        if (!isAdvanced.value || !isControlRoute(route)) {
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

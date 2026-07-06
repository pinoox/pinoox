import {computed} from 'vue';
import {useRouteMeta} from '@/views/composables/useRouteMeta.js';
import {useAppViewMode} from '@/views/composables/useAppViewMode.js';
import {useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';
import {useMarketWindowStore} from '@/stores/modules/marketWindow.js';

export function useManagerChrome() {
    const {hasToolbar: routeHasToolbar, isSingle, showDock} = useRouteMeta();
    const {isAdvanced} = useAppViewMode();
    const controlPanelWindow = useControlPanelWindowStore();
    const marketWindow = useMarketWindowStore();

    const hasToolbar = computed(() => {
        if (routeHasToolbar.value) {
            return true;
        }

        if (!isAdvanced.value) {
            return false;
        }

        return (controlPanelWindow.mode === 'floating' && controlPanelWindow.isVisible)
            || (marketWindow.mode === 'floating' && marketWindow.isVisible);
    });

    const showDockBar = computed(() => {
        if (showDock.value) {
            return true;
        }

        if (!isAdvanced.value) {
            return false;
        }

        return controlPanelWindow.mode === 'floating'
            || controlPanelWindow.mode === 'minimized'
            || marketWindow.mode === 'floating'
            || marketWindow.mode === 'minimized';
    });

    return {
        hasToolbar,
        isSingle,
        showDockBar,
    };
}

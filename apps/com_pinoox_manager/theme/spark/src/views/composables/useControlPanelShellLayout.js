import {computed, onMounted, onUnmounted, unref, watch} from 'vue';
import {useControlPanelLayoutStore} from '@/stores/modules/controlPanelLayout.js';
import {useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';

export function useControlPanelShellLayout(shellRef, isFloating) {
    const layout = useControlPanelLayoutStore();
    const controlPanelWindow = useControlPanelWindowStore();
    const isVisible = computed(() => controlPanelWindow.isVisible);

    let shellObserver = null;

    function updateShellWidth() {
        layout.bindViewport();

        if (!unref(isVisible)) {
            layout.clearFrameWidth();
            return;
        }

        const width = shellRef.value?.offsetWidth ?? 0;

        if (width > 0) {
            layout.setFrameWidth(width);
            return;
        }

        layout.syncBreakpoints();
    }

    function bindShellObserver() {
        if (typeof ResizeObserver === 'undefined' || !shellRef.value) {
            return;
        }

        shellObserver?.disconnect();
        shellObserver = new ResizeObserver(updateShellWidth);
        shellObserver.observe(shellRef.value);
    }

    watch([isFloating, isVisible], updateShellWidth, {immediate: true});

    onMounted(() => {
        layout.bindViewport();
        bindShellObserver();
        updateShellWidth();
    });

    onUnmounted(() => {
        shellObserver?.disconnect();
        shellObserver = null;
        layout.clearFrameWidth();
    });

    return {
        updateShellWidth,
    };
}

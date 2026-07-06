import {computed, unref} from 'vue';
import {useAppViewFloating} from '@/views/composables/useAppViewFloating.js';

export function useManagerWindowFloating(options) {
    const {
        shellRef,
        overlay,
        fullscreen,
        zIndex,
        sessionRect,
        onRectCommit,
        onFocus,
        onInteract,
    } = options;

    const isFloating = computed(() => unref(overlay));
    const isFullscreen = computed(() => unref(fullscreen));

    const panelStyle = computed(() => {
        if (!isFloating.value && !isFullscreen.value) {
            return {};
        }

        return {zIndex: unref(zIndex)};
    });

    const rectSource = computed(() => unref(sessionRect));

    const {
        shellStyle: floatingStyle,
        onDragStart,
        onResizeStart,
        interacting,
        isDragging,
        isResizing,
    } = useAppViewFloating(shellRef, rectSource, {
        onRectCommit,
        onInteract,
    });

    function onPanelFocus() {
        if (isFloating.value) {
            onFocus?.();
        }
    }

    function onToolbarMouseDown(event) {
        if (!isFloating.value) {
            return;
        }

        onFocus?.();
        onDragStart(event);
    }

    return {
        isFloating,
        isFullscreen,
        panelStyle,
        floatingStyle,
        interacting,
        isDragging,
        isResizing,
        onPanelFocus,
        onToolbarMouseDown,
        onResizeStart,
    };
}

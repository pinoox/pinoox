import {defineStore} from 'pinia';
import {
    defaultControlPanelRect,
    useControlPanelLayoutStore,
} from '@/stores/modules/controlPanelLayout.js';

export const CONTROL_PANEL_ID = 'control';

export const useControlPanelWindowStore = defineStore('controlPanelWindow', {
    state: () => ({
        mode: 'hidden',
        rect: defaultControlPanelRect(),
        zIndex: 10050,
        topZ: 10050,
        restoreMode: 'fullscreen',
        lastPath: '/control/apps',
    }),
    getters: {
        isOpen(state) {
            return state.mode === 'floating'
                || state.mode === 'fullscreen'
                || state.mode === 'minimized';
        },
        isActive(state) {
            return state.mode === 'floating' || state.mode === 'fullscreen';
        },
        isVisible(state) {
            return state.mode === 'floating' || state.mode === 'fullscreen';
        },
        isMinimized(state) {
            return state.mode === 'minimized';
        },
    },
    actions: {
        focus() {
            this.topZ += 1;
            this.zIndex = this.topZ;
        },
        updateRect(rect) {
            this.rect = {...rect};
        },
        setLastPath(path) {
            const normalized = String(path ?? '').trim();

            if (normalized.startsWith('/control')) {
                this.lastPath = normalized;
            }
        },
        syncFloatingRect() {
            const layout = useControlPanelLayoutStore();

            this.rect = defaultControlPanelRect(layout.isMobile);
        },
        openFullscreen() {
            this.mode = 'fullscreen';
            this.focus();
        },
        enterFloating(resetRect = true) {
            if (resetRect) {
                this.syncFloatingRect();
            }

            this.mode = 'floating';
            this.focus();
        },
        minimize(restoreMode = 'fullscreen', path = null) {
            this.restoreMode = restoreMode === 'floating' ? 'floating' : 'fullscreen';

            if (path) {
                this.setLastPath(path);
            }

            this.mode = 'minimized';
        },
        restoreSession() {
            const restoreMode = this.restoreMode === 'floating' ? 'floating' : 'fullscreen';

            if (restoreMode === 'fullscreen') {
                this.openFullscreen();
            } else {
                this.enterFloating(false);
            }

            return restoreMode;
        },
        close() {
            this.mode = 'hidden';
        },
        dismiss() {
            this.mode = 'hidden';
            this.restoreMode = 'fullscreen';
        },
    },
});

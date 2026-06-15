import {defineStore} from 'pinia';

export const CONTROL_PANEL_ID = 'control';

function defaultRect() {
    const vw = window.innerWidth;
    const vh = window.innerHeight;

    return {
        x: Math.round(vw * 0.11),
        y: Math.round(vh * 0.09),
        w: Math.round(vw * 0.78),
        h: Math.round(vh * 0.82),
    };
}

export const useControlPanelWindowStore = defineStore('controlPanelWindow', {
    state: () => ({
        mode: 'hidden',
        rect: defaultRect(),
        zIndex: 10050,
        topZ: 10050,
        restoreMode: 'fullscreen',
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
        openFullscreen() {
            this.mode = 'fullscreen';
            this.focus();
        },
        enterFloating() {
            this.mode = 'floating';
            this.focus();
        },
        minimize(restoreMode = 'fullscreen') {
            this.restoreMode = restoreMode === 'floating' ? 'floating' : 'fullscreen';
            this.mode = 'minimized';
        },
        restoreSession() {
            const restoreMode = this.restoreMode === 'floating' ? 'floating' : 'fullscreen';

            if (restoreMode === 'fullscreen') {
                this.openFullscreen();
            } else {
                this.enterFloating();
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

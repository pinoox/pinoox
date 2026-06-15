import {defineStore} from 'pinia';

export const MOBILE_MAX = 768;
export const COMPACT_MAX = 1024;
const DOCK_CLEARANCE = 112;

let resizeHandler = null;

export function fitControlPanelRectAboveDock(rect) {
    const maxBottom = window.innerHeight - DOCK_CLEARANCE;
    const next = {...rect};

    if (next.y + next.h > maxBottom) {
        next.h = Math.max(280, maxBottom - next.y);
    }

    return next;
}

export function defaultControlPanelRect(mobile = false) {
    const vw = window.innerWidth;
    const vh = window.innerHeight;

    if (mobile) {
        return fitControlPanelRectAboveDock({
            x: 8,
            y: 8,
            w: Math.max(280, vw - 16),
            h: Math.max(320, vh - 16),
        });
    }

    return fitControlPanelRectAboveDock({
        x: Math.round(vw * 0.11),
        y: Math.round(vh * 0.09),
        w: Math.round(vw * 0.78),
        h: Math.round(vh * 0.82),
    });
}

export const useControlPanelLayoutStore = defineStore('controlPanelLayout', {
    state: () => ({
        isMobile: false,
        isCompact: false,
        mobileSidebarOpen: false,
        viewportBound: false,
        frameWidthSource: false,
        frameWidth: 0,
    }),
    getters: {
        showMenuToggle(state) {
            return state.isCompact;
        },
    },
    actions: {
        syncBreakpoints() {
            const viewportWidth = window.innerWidth;
            const frameWidth = this.frameWidthSource ? this.frameWidth : 0;
            const widths = [viewportWidth];

            if (frameWidth > 0) {
                widths.push(frameWidth);
            }

            const minWidth = Math.min(...widths);

            this.isMobile = minWidth <= MOBILE_MAX || viewportWidth <= MOBILE_MAX;
            this.isCompact = minWidth <= COMPACT_MAX || viewportWidth <= COMPACT_MAX;

            if (!this.isCompact) {
                this.mobileSidebarOpen = false;
            }
        },
        syncFromViewport() {
            this.syncBreakpoints();
        },
        bindViewport() {
            if (this.viewportBound) {
                this.syncBreakpoints();
                return;
            }

            this.viewportBound = true;

            const update = () => {
                this.syncBreakpoints();
            };

            update();
            resizeHandler = update;
            window.addEventListener('resize', update);
        },
        unbindViewport() {
            if (!this.viewportBound || !resizeHandler) {
                return;
            }

            window.removeEventListener('resize', resizeHandler);
            resizeHandler = null;
            this.viewportBound = false;
        },
        setFrameWidth(width) {
            this.frameWidthSource = true;
            this.frameWidth = Math.max(0, Math.round(width));
            this.syncBreakpoints();
        },
        clearFrameWidth() {
            this.frameWidthSource = false;
            this.frameWidth = 0;
            this.syncBreakpoints();
        },
        toggleMobileSidebar() {
            this.mobileSidebarOpen = !this.mobileSidebarOpen;
        },
        closeMobileSidebar() {
            this.mobileSidebarOpen = false;
        },
    },
});

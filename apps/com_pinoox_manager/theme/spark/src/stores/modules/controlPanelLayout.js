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
    actions: {
        applyBreakpoints(width) {
            const safeWidth = Math.max(0, Math.round(width));

            this.isMobile = safeWidth <= MOBILE_MAX;
            this.isCompact = safeWidth <= COMPACT_MAX;

            if (!this.isMobile) {
                this.mobileSidebarOpen = false;
            }
        },
        syncFromViewport() {
            this.applyBreakpoints(window.innerWidth);
        },
        bindViewport() {
            if (this.viewportBound) {
                return;
            }

            this.viewportBound = true;

            const update = () => {
                if (!this.frameWidthSource) {
                    this.syncFromViewport();
                }
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
            this.applyBreakpoints(this.frameWidth);
        },
        clearFrameWidth() {
            this.frameWidthSource = false;
            this.frameWidth = 0;
            this.syncFromViewport();
        },
        toggleMobileSidebar() {
            this.mobileSidebarOpen = !this.mobileSidebarOpen;
        },
        closeMobileSidebar() {
            this.mobileSidebarOpen = false;
        },
    },
});

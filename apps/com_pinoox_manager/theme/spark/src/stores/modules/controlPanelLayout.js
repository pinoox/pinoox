import {defineStore} from 'pinia';

const MOBILE_MAX = 768;
const COMPACT_MAX = 1024;
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
    }),
    actions: {
        bindViewport() {
            if (this.viewportBound) {
                return;
            }

            this.viewportBound = true;

            const update = () => {
                const width = window.innerWidth;

                this.isMobile = width <= MOBILE_MAX;
                this.isCompact = width <= COMPACT_MAX;

                if (!this.isMobile) {
                    this.mobileSidebarOpen = false;
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
        toggleMobileSidebar() {
            this.mobileSidebarOpen = !this.mobileSidebarOpen;
        },
        closeMobileSidebar() {
            this.mobileSidebarOpen = false;
        },
    },
});

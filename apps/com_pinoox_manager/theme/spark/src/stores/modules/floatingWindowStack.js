import {useAppViewWindowStore} from '@/stores/modules/appViewWindow.js';
import {useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';
import {useMarketWindowStore} from '@/stores/modules/marketWindow.js';

export function getTopFloatingZIndex() {
    const appViewWindow = useAppViewWindowStore();
    const controlPanelWindow = useControlPanelWindowStore();
    const marketWindow = useMarketWindowStore();
    let topZ = -1;

    for (const session of Object.values(appViewWindow.sessions)) {
        if (session.mode === 'floating' && session.zIndex > topZ) {
            topZ = session.zIndex;
        }
    }

    if (
        controlPanelWindow.mode === 'floating'
        && controlPanelWindow.zIndex > topZ
    ) {
        topZ = controlPanelWindow.zIndex;
    }

    if (
        marketWindow.mode === 'floating'
        && marketWindow.zIndex > topZ
    ) {
        topZ = marketWindow.zIndex;
    }

    return topZ;
}

export function isControlPanelFloatingTopmost() {
    const controlPanelWindow = useControlPanelWindowStore();

    if (controlPanelWindow.mode !== 'floating') {
        return false;
    }

    return controlPanelWindow.zIndex >= getTopFloatingZIndex();
}

export function isMarketFloatingTopmost() {
    const marketWindow = useMarketWindowStore();

    if (marketWindow.mode !== 'floating') {
        return false;
    }

    return marketWindow.zIndex >= getTopFloatingZIndex();
}

export function bumpSharedFloatingZIndex() {
    const appViewWindow = useAppViewWindowStore();
    const controlPanelWindow = useControlPanelWindowStore();
    const marketWindow = useMarketWindowStore();
    const nextZ = Math.max(
        appViewWindow.topZ,
        controlPanelWindow.topZ,
        marketWindow.topZ,
        getTopFloatingZIndex(),
    ) + 1;

    appViewWindow.topZ = nextZ;
    controlPanelWindow.topZ = nextZ;
    marketWindow.topZ = nextZ;

    return nextZ;
}

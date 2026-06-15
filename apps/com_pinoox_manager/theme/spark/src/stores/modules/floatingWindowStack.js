import {useAppViewWindowStore} from '@/stores/modules/appViewWindow.js';
import {useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';

export function getTopFloatingZIndex() {
    const appViewWindow = useAppViewWindowStore();
    const controlPanelWindow = useControlPanelWindowStore();
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

    return topZ;
}

export function isControlPanelFloatingTopmost() {
    const controlPanelWindow = useControlPanelWindowStore();

    if (controlPanelWindow.mode !== 'floating') {
        return false;
    }

    return controlPanelWindow.zIndex >= getTopFloatingZIndex();
}

export function bumpSharedFloatingZIndex() {
    const appViewWindow = useAppViewWindowStore();
    const controlPanelWindow = useControlPanelWindowStore();
    const nextZ = Math.max(
        appViewWindow.topZ,
        controlPanelWindow.topZ,
        getTopFloatingZIndex(),
    ) + 1;

    appViewWindow.topZ = nextZ;
    controlPanelWindow.topZ = nextZ;

    return nextZ;
}

/** @see apps/com_pinoox_manager/theme/spark/app-view-error.twig */
export const APP_VIEW_BRIDGE = Object.freeze({
    CLOSE: 'pinoox:manager:app-view:close',
    SOURCE: 'pinoox-app-view',
});

export function isAppViewCloseMessage(data) {
    if (data === APP_VIEW_BRIDGE.CLOSE) {
        return true;
    }

    return typeof data === 'object'
        && data !== null
        && data.type === APP_VIEW_BRIDGE.CLOSE
        && data.source === APP_VIEW_BRIDGE.SOURCE;
}

export function postAppViewClose() {
    if (window.self === window.top) {
        return false;
    }

    window.parent.postMessage(
        {
            type: APP_VIEW_BRIDGE.CLOSE,
            source: APP_VIEW_BRIDGE.SOURCE,
        },
        window.location.origin,
    );

    return true;
}

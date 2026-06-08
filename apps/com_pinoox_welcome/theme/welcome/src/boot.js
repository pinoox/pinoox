export function getBoot() {
    return globalThis.__PINOOX__ ?? {};
}

export function getUrl() {
    return getBoot().url ?? {};
}

/** @deprecated use getBoot() */
export const boot = getBoot();

/** @deprecated use getUrl() */
export const url = getUrl();

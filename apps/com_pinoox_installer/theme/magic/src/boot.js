export function getBoot() {
    return globalThis.__PINOOX__ ?? {};
}

export function getUrl() {
    return getBoot().url ?? {};
}

export function hasBoot() {
    const url = getUrl();

    return typeof globalThis.__PINOOX__ !== 'undefined'
        && typeof url.APP === 'string'
        && url.APP !== '';
}

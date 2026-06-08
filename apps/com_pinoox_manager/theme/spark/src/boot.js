export function getBoot() {
    return globalThis.__PINOOX__ ?? {};
}

export function getUrl() {
    return getBoot().url ?? {};
}

export function hasBoot() {
    return typeof globalThis.__PINOOX__ !== 'undefined';
}

export function getBoot() {
    return globalThis.__PINOOX__ ?? {};
}

export function getUrl() {
    return getBoot().url ?? {};
}

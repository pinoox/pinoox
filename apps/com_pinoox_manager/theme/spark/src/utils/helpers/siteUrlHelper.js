export function stripUrlProtocol(url) {
    return String(url ?? '').replace(/^https?:\/\//i, '');
}

/** Site origin for UI labels (no protocol, no trailing slash). */
export function formatSiteOriginForDisplay(siteUrl) {
    return stripUrlProtocol(siteUrl).replace(/\/+$/, '');
}

/** Input prefix: localhost/pinoox/ */
export function formatSiteOriginPrefix(siteUrl) {
    const base = formatSiteOriginForDisplay(siteUrl);

    if (!base) {
        return '/';
    }

    return `${base}/`;
}

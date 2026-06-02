export function buildSecretViewEmbedUrl(packageName) {
    const base = typeof PINOOX !== 'undefined' ? PINOOX.URL.APP : '/';
    const token = localStorage.getItem('manager_pinoox');
    const params = token ? `?__manager_token=${encodeURIComponent(token)}` : '';

    return `${base}/app/${packageName}${params}`;
}

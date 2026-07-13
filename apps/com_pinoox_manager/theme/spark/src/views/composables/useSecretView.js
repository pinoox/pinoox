import { getUrl } from '@/boot.js';
import { auth } from '@/lib/auth/client.js';

export function buildSecretViewEmbedUrl(packageName) {
    const base = getUrl().APP || '/';
    const token = auth.getToken();
    const params = token ? `?__manager_token=${encodeURIComponent(token)}` : '';

    return `${base}/app/${packageName}${params}`;
}

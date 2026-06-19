export const DEFAULT_FALLBACK_BACKGROUND =
    'linear-gradient(145deg, #1a1a2e 0%, #16213e 45%, #0f3460 100%)';

/**
 * Wallpaper API routes use ids without file extensions.
 * Strip legacy ".jpg" suffixes from cached URLs (php built-in server / Symfony routing).
 */
export function normalizeWallpaperUrl(url) {
    if (!url || typeof url !== 'string')
        return url;

    return url.replace(
        /\/wallpapers\/([^/?#]+)\.(?:jpe?g|png|webp)(?=$|[?#])/i,
        '/wallpapers/$1',
    );
}

export function wallpaperUrl(wallpapers, id, defaultId = null) {
    if (!wallpapers?.length)
        return null;

    const normalizedId = String(id ?? '');
    const item = wallpapers.find((wallpaper) => wallpaper.id === normalizedId);
    if (item?.url)
        return normalizeWallpaperUrl(item.url);

    const fallbackId = defaultId ?? wallpapers[0]?.id;
    const fallback = wallpapers.find((wallpaper) => wallpaper.id === String(fallbackId));
    const url = fallback?.url ?? wallpapers[0]?.url ?? null;

    return url ? normalizeWallpaperUrl(url) : null;
}

export function resolveWallpaperId(wallpapers, selected, defaultId = null) {
    if (!wallpapers?.length)
        return '';

    const fallback = String(defaultId ?? wallpapers[0]?.id ?? '');
    const id = String(selected ?? fallback);

    if (wallpapers.some((wallpaper) => wallpaper.id === id))
        return id;

    return fallback;
}

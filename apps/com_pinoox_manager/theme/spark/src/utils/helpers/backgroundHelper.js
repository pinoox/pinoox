export function wallpaperUrl(wallpapers, id, defaultId = null) {
    const normalizedId = String(id ?? '');
    const item = wallpapers?.find((wallpaper) => wallpaper.id === normalizedId);
    if (item?.url)
        return item.url;

    const fallbackId = defaultId ?? wallpapers?.[0]?.id;
    const fallback = wallpapers?.find((wallpaper) => wallpaper.id === String(fallbackId));
    return fallback?.url ?? wallpapers?.[0]?.url ?? null;
}

export function resolveWallpaperId(wallpapers, selected, defaultId = null) {
    const fallback = String(defaultId ?? wallpapers?.[0]?.id ?? '1');
    const id = String(selected ?? fallback);

    if (wallpapers?.some((wallpaper) => wallpaper.id === id))
        return id;

    return fallback;
}

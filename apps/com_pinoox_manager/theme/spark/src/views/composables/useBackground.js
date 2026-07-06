import { ref, watch, provide, inject, computed } from "vue";
import { useOptionsStore } from "@/stores/modules/options.js";

import { wallpaperUrl, normalizeWallpaperUrl } from "@utils/helpers/backgroundHelper.js";

const WALLPAPER_CACHE_KEY = 'manager_wallpaper_url';

function readCachedWallpaper() {
    try {
        return localStorage.getItem(WALLPAPER_CACHE_KEY) || null;
    } catch {
        return null;
    }
}

function writeCachedWallpaper(url) {
    if (!url)
        return;

    try {
        localStorage.setItem(WALLPAPER_CACHE_KEY, url);
    } catch {
        // ignore quota / private mode
    }
}

function preloadImage(url) {

    if (!url)

        return Promise.resolve();



    return new Promise((resolve) => {

        const image = new Image();

        image.onload = () => resolve();

        image.onerror = () => resolve();

        image.src = url;

    });

}



export function useBackground() {

    const optionsStore = useOptionsStore();

    let selectedBackground = inject("selectedBackground", ref(null));

    const changingBackgroundId = ref(null);

    const uploadingWallpaper = ref(false);

    const deletingBackgroundId = ref(null);



    const isBusy = computed(() => Boolean(

        changingBackgroundId.value || uploadingWallpaper.value || deletingBackgroundId.value,

    ));



    const backgrounds = computed(() => optionsStore.wallpapers);



    const selectedId = computed(() => String(optionsStore.background || optionsStore.defaultBackground || ''));



    const syncBackground = () => {

        const url = normalizeWallpaperUrl(optionsStore.backgroundUrl || readCachedWallpaper() || null);

        selectedBackground.value = url;

        if (url)

            writeCachedWallpaper(url);

    };



    if (!selectedBackground.value)

        syncBackground();



    watch(() => optionsStore.isLoaded, syncBackground);

    watch(() => optionsStore.background, syncBackground);

    watch(() => optionsStore.wallpapers, syncBackground, { deep: true });

    watch(selectedBackground, (url) => {

        if (url)

            writeCachedWallpaper(url);

    });



    const changeBackground = async (item) => {

        const name = String(typeof item === 'object' ? item.id : item);



        if (isBusy.value || selectedId.value === name)

            return;



        changingBackgroundId.value = name;



        try {

            await optionsStore.changeBackground(name);



            const url = wallpaperUrl(

                optionsStore.wallpapers,

                optionsStore.background,

                optionsStore.defaultBackground,

            );



            await preloadImage(url);

            selectedBackground.value = url || null;

        } finally {

            changingBackgroundId.value = null;

        }

    };



    const uploadWallpaper = async (file, { select = true } = {}) => {

        if (!file || isBusy.value)

            return null;



        uploadingWallpaper.value = true;



        try {

            const wallpaper = await optionsStore.uploadWallpaper(file);

            if (!wallpaper)

                return null;



            if (select) {

                uploadingWallpaper.value = false;

                await changeBackground(wallpaper);

            }



            return wallpaper;

        } finally {

            uploadingWallpaper.value = false;

        }

    };



    const deleteWallpaper = async (item) => {

        const id = String(typeof item === 'object' ? item.id : item);



        if (isBusy.value)

            return;



        deletingBackgroundId.value = id;



        try {

            await optionsStore.deleteWallpaper(id);



            const url = wallpaperUrl(

                optionsStore.wallpapers,

                optionsStore.background,

                optionsStore.defaultBackground,

            );



            await preloadImage(url);

            selectedBackground.value = url || null;

        } finally {

            deletingBackgroundId.value = null;

        }

    };



    provide("selectedBackground", selectedBackground);



    return {

        backgrounds,

        selectedBackground,

        selectedId,

        changingBackgroundId,

        uploadingWallpaper,

        deletingBackgroundId,

        isBusy,

        changeBackground,

        uploadWallpaper,

        deleteWallpaper,

    };

}



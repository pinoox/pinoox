import { ref, watch, provide, inject, computed } from "vue";
import { useOptionsStore } from "@/stores/modules/options.js";
import { wallpaperUrl } from "@utils/helpers/backgroundHelper.js";

export function useBackground() {
    const optionsStore = useOptionsStore();
    let selectedBackground = inject("selectedBackground", ref(null));

    const backgrounds = computed(() => optionsStore.wallpapers);

    const syncBackground = () => {
        const url = optionsStore.backgroundUrl;
        if (url)
            selectedBackground.value = url;
    };

    if (!selectedBackground.value)
        syncBackground();

    watch(() => optionsStore.isLoaded, syncBackground);
    watch(() => optionsStore.background, syncBackground);
    watch(() => optionsStore.wallpapers, syncBackground, { deep: true });

    const changeBackground = async (item) => {
        const name = typeof item === 'object' ? item.id : item;
        await optionsStore.changeBackground(name);
        selectedBackground.value = wallpaperUrl(
            optionsStore.wallpapers,
            optionsStore.background,
            optionsStore.defaultBackground,
        );
    };

    provide("selectedBackground", selectedBackground);

    return {
        backgrounds,
        selectedBackground,
        selectedId: computed(() => String(optionsStore.background || optionsStore.defaultBackground || '1')),
        changeBackground,
    };
}

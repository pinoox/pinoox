import { ref, watch, provide, inject } from "vue";

export function useBackground() {
    let selectedBackground = inject("selectedBackground", ref(null));

    const backgrounds = [
        new URL('@/assets/media/bg/1.webp', import.meta.url).href,
        new URL('@/assets/media/bg/2.webp', import.meta.url).href,
        new URL('@/assets/media/bg/3.webp', import.meta.url).href,
        new URL('@/assets/media/bg/4.webp', import.meta.url).href,
        new URL('@/assets/media/bg/5.webp', import.meta.url).href,
        new URL('@/assets/media/bg/6.webp', import.meta.url).href,
        new URL('@/assets/media/bg/7.webp', import.meta.url).href,
    ];

    if (!selectedBackground.value) {
        selectedBackground.value = localStorage.getItem("selectedBackground") || backgrounds[0];
    }

    const changeBackground = (image) => {
        selectedBackground.value = image;
        localStorage.setItem("selectedBackground", image);
    };

    provide("selectedBackground", selectedBackground);

    return { backgrounds, selectedBackground, changeBackground };
}
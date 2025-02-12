import { ref } from "vue";

export function useBackground() {
    const backgrounds = [
        new URL('@/assets/media/bg/1.webp', import.meta.url).href,
        new URL('@/assets/media/bg/2.webp', import.meta.url).href,
        new URL('@/assets/media/bg/3.webp', import.meta.url).href,
        new URL('@/assets/media/bg/4.webp', import.meta.url).href,
    ];

    const selectedBackground = ref(localStorage.getItem("selectedBackground") || backgrounds[1]);

    const changeBackground = (image) => {
        selectedBackground.value = image;
        localStorage.setItem("selectedBackground", image);
    };

    return { backgrounds, selectedBackground, changeBackground };
}

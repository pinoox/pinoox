import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { useDebounceFn } from '@vueuse/core';

const LUMINANCE_THRESHOLD = 132;

function relativeLuminance(r, g, b) {
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

function coverMetrics(image, viewportWidth, viewportHeight) {
    const scale = Math.max(viewportWidth / image.width, viewportHeight / image.height);
    const drawnWidth = image.width * scale;
    const drawnHeight = image.height * scale;

    return {
        scale,
        offsetX: (viewportWidth - drawnWidth) / 2,
        offsetY: (viewportHeight - drawnHeight) / 2,
    };
}

function sampleRegionTone(image, rect) {
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    const { scale, offsetX, offsetY } = coverMetrics(image, viewportWidth, viewportHeight);

    const sourceX = (rect.left - offsetX) / scale;
    const sourceY = (rect.top - offsetY) / scale;
    const sourceWidth = rect.width / scale;
    const sourceHeight = rect.height / scale;

    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d', { willReadFrequently: true });

    if (!context)
        return null;

    canvas.width = Math.max(1, Math.round(sourceWidth));
    canvas.height = Math.max(1, Math.round(sourceHeight));

    context.drawImage(
        image,
        sourceX,
        sourceY,
        sourceWidth,
        sourceHeight,
        0,
        0,
        canvas.width,
        canvas.height,
    );

    const { data } = context.getImageData(0, 0, canvas.width, canvas.height);
    let luminanceSum = 0;
    let count = 0;

    for (let index = 0; index < data.length; index += 4) {
        if (data[index + 3] < 16)
            continue;

        luminanceSum += relativeLuminance(data[index], data[index + 1], data[index + 2]);
        count++;
    }

    if (!count)
        return null;

    return luminanceSum / count >= LUMINANCE_THRESHOLD ? 'light' : 'dark';
}

function loadImage(url) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.decoding = 'async';

        image.onload = () => resolve(image);
        image.onerror = reject;
        image.src = url;
    });
}

export function useDockBackdropTone(backgroundUrl, targetRef) {
    const tone = ref('dark');

    let cancelled = false;

    const measure = useDebounceFn(async () => {
        const url = backgroundUrl.value;
        const element = targetRef.value;

        if (!url || !element) {
            tone.value = 'dark';
            return;
        }

        try {
            const image = await loadImage(url);

            if (cancelled)
                return;

            const rect = element.getBoundingClientRect();

            if (!rect.width || !rect.height) {
                tone.value = 'dark';
                return;
            }

            const nextTone = sampleRegionTone(image, rect);

            if (nextTone)
                tone.value = nextTone;
        } catch {
            if (!cancelled)
                tone.value = 'dark';
        }
    }, 120);

    let resizeObserver = null;

    onMounted(() => {
        measure();

        window.addEventListener('resize', measure);

        if (targetRef.value && typeof ResizeObserver !== 'undefined') {
            resizeObserver = new ResizeObserver(measure);
            resizeObserver.observe(targetRef.value);
        }
    });

    onBeforeUnmount(() => {
        cancelled = true;
        window.removeEventListener('resize', measure);
        resizeObserver?.disconnect();
    });

    watch(backgroundUrl, () => {
        measure();
    });

    watch(targetRef, (element, previous) => {
        if (resizeObserver && previous)
            resizeObserver.unobserve(previous);

        if (resizeObserver && element)
            resizeObserver.observe(element);

        measure();
    });

    return { tone, remeasure: measure };
}

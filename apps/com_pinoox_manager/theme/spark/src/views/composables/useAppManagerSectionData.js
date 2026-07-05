import {onMounted, ref, unref, watch} from 'vue';

const sectionCache = new Map();

function resolveCacheKey(section, packageName) {
    return `${section}:${packageName ?? ''}`;
}

export function useAppManagerSectionData(section, packageNameSource, fetcher) {
    const items = ref([]);
    const isLoading = ref(true);
    const isRefreshing = ref(false);
    const hasCachedData = ref(false);

    function readCache() {
        const cacheKey = resolveCacheKey(section, unref(packageNameSource));
        const cached = sectionCache.get(cacheKey);

        if (cached !== undefined) {
            items.value = cached;
            hasCachedData.value = true;
            isLoading.value = false;
            return true;
        }

        hasCachedData.value = false;
        return false;
    }

    async function load({force = false} = {}) {
        const packageName = unref(packageNameSource);

        if (!packageName) {
            items.value = [];
            isLoading.value = false;
            isRefreshing.value = false;
            hasCachedData.value = false;
            return;
        }

        const cacheKey = resolveCacheKey(section, packageName);

        if (!force && sectionCache.has(cacheKey)) {
            items.value = sectionCache.get(cacheKey);
            hasCachedData.value = true;
            isLoading.value = false;
            return;
        }

        if (sectionCache.has(cacheKey) || hasCachedData.value) {
            isRefreshing.value = true;
        } else {
            isLoading.value = true;
        }

        try {
            const data = await fetcher(packageName);
            const normalized = Array.isArray(data) ? data : [];
            sectionCache.set(cacheKey, normalized);
            items.value = normalized;
            hasCachedData.value = true;
        } finally {
            isLoading.value = false;
            isRefreshing.value = false;
        }
    }

    function invalidate() {
        const cacheKey = resolveCacheKey(section, unref(packageNameSource));
        sectionCache.delete(cacheKey);
    }

    onMounted(() => {
        readCache();
        load();
    });

    watch(packageNameSource, () => {
        readCache();
        load();
    });

    return {
        items,
        isLoading,
        isRefreshing,
        hasCachedData,
        reload: () => load({force: true}),
        invalidate,
    };
}

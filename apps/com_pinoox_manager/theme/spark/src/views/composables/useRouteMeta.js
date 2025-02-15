import { computed } from 'vue';
import { useRoute } from 'vue-router';

export function useRouteMeta() {
    const route = useRoute();

    const hasToolbar = computed(() => {
        return route.meta?.toolbar !== false;
    });

    return { hasToolbar };
}
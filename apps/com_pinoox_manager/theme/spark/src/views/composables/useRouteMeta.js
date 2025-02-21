import {computed} from 'vue';
import {useRoute} from 'vue-router';

export function useRouteMeta() {
    const route = useRoute();

    const hasToolbar = computed(() => {
        return route.meta?.toolbar !== false;
    });

    const isSingle = computed(() => {
        return !!route.meta?.single;
    });

    return {hasToolbar, isSingle};
}
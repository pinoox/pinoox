import {computed} from 'vue';
import {useOptionsStore} from '@/stores/modules/options.js';

export function useAppViewMode() {
    const optionsStore = useOptionsStore();

    const isAdvanced = computed(() => optionsStore.appViewMode === 'advanced');
    const isSimple = computed(() => !isAdvanced.value);

    return {isAdvanced, isSimple};
}

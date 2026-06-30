<template>
  <a v-bind="$attrs" :href="item.href || '#'" @click="onClick">
    <slot/>
  </a>
</template>

<script setup>
import {useRouter} from 'vue-router';
import {
    isControlPanelMemoryPath,
    syncControlPanelMemoryRouter,
} from '@/router/controlPanelMemoryRouter.js';
import {useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';
import {isControlRoute} from '@/views/composables/useControlPanel.js';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['click']);

const globalRouter = useRouter();
const controlPanelWindow = useControlPanelWindowStore();

async function onClick(event) {
    emit('click', event);

    if (!props.item?.href || props.item.external || props.item.disabled) {
        return;
    }

    event.preventDefault();

    if (!isControlPanelMemoryPath(props.item.href)) {
        await globalRouter.push(props.item.href);
        return;
    }

    await syncControlPanelMemoryRouter(props.item.href);
    controlPanelWindow.setLastPath(props.item.href);

    if (isControlRoute(globalRouter.currentRoute.value)) {
        await globalRouter.push(props.item.href);
    }
}
</script>

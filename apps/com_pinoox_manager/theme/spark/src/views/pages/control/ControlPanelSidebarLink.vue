<template>
  <a v-bind="$attrs" :href="item.href || '#'" @click="onClick">
    <slot/>
  </a>
</template>

<script setup>
import {useRouter} from 'vue-router';
import {isControlPanelMemoryPath} from '@/router/controlPanelMemoryRouter.js';
import {useControlPanelNavigation} from '@/views/composables/useControlPanelNavigation.js';
import {useMarket} from '@/views/composables/useMarket.js';

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
const {pushControlPath} = useControlPanelNavigation();
const {openMarket} = useMarket();

async function onClick(event) {
    emit('click', event);

    if (!props.item?.href || props.item.external || props.item.disabled) {
        return;
    }

    event.preventDefault();

    if (props.item.href === '/market') {
        await openMarket();
        return;
    }

    if (!isControlPanelMemoryPath(props.item.href)) {
        await globalRouter.push(props.item.href);
        return;
    }

    await pushControlPath(props.item.href);
}
</script>

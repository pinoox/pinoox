<script setup>
import {provide} from 'vue';
import {routeLocationKey, routerKey} from 'vue-router';

const props = defineProps({
    router: {
        type: Object,
        required: true,
    },
});

provide(routerKey, props.router);
provide(routeLocationKey, props.router.currentRoute);
</script>

<template>
  <slot/>
</template>

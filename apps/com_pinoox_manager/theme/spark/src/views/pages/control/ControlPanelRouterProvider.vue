<script setup>
import {provide, shallowReactive} from 'vue';
import {routeLocationKey, routerKey, routerViewLocationKey, START_LOCATION} from 'vue-router';

const props = defineProps({
    router: {
        type: Object,
        required: true,
    },
});

const reactiveRoute = {};

for (const key in START_LOCATION) {
    Object.defineProperty(reactiveRoute, key, {
        get: () => props.router.currentRoute.value[key],
        enumerable: true,
    });
}

provide(routerKey, props.router);
provide(routeLocationKey, shallowReactive(reactiveRoute));
provide(routerViewLocationKey, props.router.currentRoute);
</script>

<template>
  <slot/>
</template>

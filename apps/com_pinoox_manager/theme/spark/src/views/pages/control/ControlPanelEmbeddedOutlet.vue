<template>
  <ControlPanelRouterProvider :router="memoryRouter">
    <component
        :is="pageComponent"
        v-if="pageComponent"
        :key="memoryRoute.fullPath"
        v-bind="pageProps"
    />
  </ControlPanelRouterProvider>
</template>

<script setup>
import {computed, onBeforeMount, watch} from 'vue';
import {useRoute} from 'vue-router';
import {
    getControlPanelMemoryRouter,
    resolveMemoryRoutePage,
    syncControlPanelMemoryRouter,
} from '@/router/controlPanelMemoryRouter.js';
import {useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';
import {isControlRoute} from '@/views/composables/useControlPanel.js';
import ControlPanelRouterProvider from '@/views/pages/control/ControlPanelRouterProvider.vue';

const globalRoute = useRoute();
const controlPanelWindow = useControlPanelWindowStore();
const memoryRouter = getControlPanelMemoryRouter();
const memoryRoute = computed(() => memoryRouter.currentRoute.value);

const pageComponent = computed(() => resolveMemoryRoutePage(memoryRoute.value).component);
const pageProps = computed(() => resolveMemoryRoutePage(memoryRoute.value).props);

function resolveMemoryPath() {
    if (isControlRoute(globalRoute)) {
        return globalRoute.path;
    }

    return controlPanelWindow.lastPath || '/control/apps';
}

async function bootstrapMemoryRouter() {
    await syncControlPanelMemoryRouter(resolveMemoryPath());
}

onBeforeMount(() => {
    bootstrapMemoryRouter();
});

watch(
    () => globalRoute.path,
    () => {
        if (isControlRoute(globalRoute)) {
            syncControlPanelMemoryRouter(globalRoute.path);
            controlPanelWindow.setLastPath(globalRoute.path);
        }
    },
);

watch(
    () => controlPanelWindow.lastPath,
    (path) => {
        if (controlPanelWindow.isVisible && path) {
            syncControlPanelMemoryRouter(path);
        }
    },
);
</script>

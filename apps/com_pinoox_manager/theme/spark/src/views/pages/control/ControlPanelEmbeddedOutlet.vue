<template>
  <ControlPanelRouterProvider v-if="isReady" :router="memoryRouter">
    <RouterView/>
  </ControlPanelRouterProvider>
</template>

<script setup>
import {onBeforeMount, ref, watch} from 'vue';
import {useRoute} from 'vue-router';
import {
    getControlPanelMemoryRouter,
    syncControlPanelMemoryRouter,
} from '@/router/controlPanelMemoryRouter.js';
import {useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';
import {isControlRoute} from '@/views/composables/useControlPanel.js';
import ControlPanelRouterProvider from '@/views/pages/control/ControlPanelRouterProvider.vue';

const globalRoute = useRoute();
const controlPanelWindow = useControlPanelWindowStore();
const memoryRouter = getControlPanelMemoryRouter();
const isReady = ref(false);

function resolveMemoryPath() {
    if (isControlRoute(globalRoute)) {
        return globalRoute.path;
    }

    return controlPanelWindow.lastPath || '/control/apps';
}

async function bootstrapMemoryRouter() {
    await syncControlPanelMemoryRouter(resolveMemoryPath());
}

onBeforeMount(async () => {
    await bootstrapMemoryRouter();
    isReady.value = true;
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

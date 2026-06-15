<template>
  <RouterView v-slot="{ Component, route }">
    <KeepAlive>
      <component
          :is="resolveComponent(Component, route)"
          :key="resolveKey(route)"
      />
    </KeepAlive>
  </RouterView>
</template>

<script setup>
import {computed, shallowRef, watch} from 'vue';
import {useRoute} from 'vue-router';
import {useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';
import {isControlRoute} from '@/views/composables/useControlPanel.js';

const PANEL_KEY = 'control-panel-advanced';

const globalRoute = useRoute();
const controlPanelWindow = useControlPanelWindowStore();
const frozenComponent = shallowRef(null);

const preserveContent = computed(() =>
    controlPanelWindow.mode === 'floating'
    || controlPanelWindow.mode === 'minimized',
);

function resolveComponent(Component, route) {
  if (Component && isControlRoute(route)) {
    frozenComponent.value = Component;
    return Component;
  }

  if (preserveContent.value && frozenComponent.value) {
    return frozenComponent.value;
  }

  frozenComponent.value = null;
  return Component;
}

function resolveKey(route) {
  if (preserveContent.value && frozenComponent.value && !isControlRoute(globalRoute)) {
    return PANEL_KEY;
  }

  if (isControlRoute(route)) {
    return `${PANEL_KEY}:${route.path}`;
  }

  return PANEL_KEY;
}

watch(
    () => controlPanelWindow.mode,
    (mode) => {
      if (mode === 'hidden') {
        frozenComponent.value = null;
      }
    },
);
</script>

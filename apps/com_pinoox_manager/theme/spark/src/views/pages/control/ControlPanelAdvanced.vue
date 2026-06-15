<template>
  <Teleport to="body">
    <ControlPanelPanelAdvanced
        v-show="controlPanelWindow.isVisible"
        :overlay="controlPanelWindow.mode === 'floating'"
        :fullscreen="controlPanelWindow.mode === 'fullscreen'"
        :z-index="controlPanelWindow.zIndex"
    />
  </Teleport>
</template>

<script setup>
import {watch} from 'vue';
import {useRoute} from 'vue-router';
import {useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';
import {isControlRoute} from '@/views/composables/useControlPanel.js';
import {useControlPanelLayoutStore} from '@/stores/modules/controlPanelLayout.js';
import {useAppViewMode} from '@/views/composables/useAppViewMode.js';
import ControlPanelPanelAdvanced from '@/views/pages/control/ControlPanelPanelAdvanced.vue';

const route = useRoute();
const controlPanelWindow = useControlPanelWindowStore();
const layout = useControlPanelLayoutStore();
const {isSimple} = useAppViewMode();

function syncRouteSession() {
  if (isSimple.value || !isControlRoute(route)) {
    return;
  }

  if (controlPanelWindow.mode === 'minimized') {
    controlPanelWindow.restoreSession();
    return;
  }

  if (controlPanelWindow.mode === 'floating') {
    controlPanelWindow.focus();
    return;
  }

  if (controlPanelWindow.mode === 'hidden') {
    controlPanelWindow.openFullscreen();
  }
}

function syncControlPanelOnLeave() {
  if (!controlPanelWindow.isOpen || controlPanelWindow.isMinimized) {
    return;
  }

  if (controlPanelWindow.mode !== 'fullscreen') {
    return;
  }

  controlPanelWindow.minimize('fullscreen', controlPanelWindow.lastPath);
}

watch(
    () => route.path,
    () => {
      if (isSimple.value) {
        return;
      }

      if (isControlRoute(route)) {
        controlPanelWindow.setLastPath(route.path);
        syncRouteSession();
        return;
      }

      syncControlPanelOnLeave();
    },
    {immediate: true},
);

watch(isSimple, (simple) => {
  if (simple) {
    controlPanelWindow.dismiss();
  }
});

watch(() => layout.isMobile, () => {
  if (
      controlPanelWindow.mode === 'floating'
      && !layout.frameWidthSource
  ) {
    controlPanelWindow.syncFloatingRect();
  }
});
</script>

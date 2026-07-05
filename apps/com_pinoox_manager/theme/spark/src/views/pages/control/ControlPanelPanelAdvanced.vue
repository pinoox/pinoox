<template>
  <ManagerWindow
      ref="windowRef"
      mode="advanced"
      :overlay="overlay"
      :fullscreen="fullscreen"
      :z-index="zIndex"
      root-class="managerWindow controlPanelWindow"
      toolbar-class="managerWindow__toolbar controlPanelWindow__toolbar"
      title-class="managerWindow__title controlPanelWindow__title"
      body-class="managerWindow__body controlPanelWindow__body @container"
      :session-rect="sessionRect"
      :shell-compact="layout.isCompact"
      :on-rect-commit="commitRect"
      :on-focus="focusWindow"
      @close="closeWindow"
      @minimize="minimizeWindow"
      @toggle-float="toggleFloating"
  >
    <template #toolbar-before>
      <ControlPanelMenuToggle
          v-if="layout.isCompact"
          @click.stop
          @mousedown.stop
      />
    </template>

    <template #title>
      <Icon :is="saxIcon.control" class="appView__title-icon" size="sm"/>
      <span>کنترل پنل</span>
    </template>

    <PageControl embedded/>
  </ManagerWindow>
</template>

<script setup>
import {computed, nextTick, onMounted, ref, watch} from 'vue';
import {useRouter} from 'vue-router';
import {saxIcon} from '@/const/icons.js';
import Icon from '@/views/components/widgets/Icon.vue';
import ManagerWindow from '@/views/components/layouts/ManagerWindow.vue';
import {useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';
import {fitControlPanelRectAboveDock} from '@/stores/modules/controlPanelLayout.js';
import {useControlPanelLayoutStore} from '@/stores/modules/controlPanelLayout.js';
import {useControlPanelShellLayout} from '@/views/composables/useControlPanelShellLayout.js';
import {isControlRoute} from '@/views/composables/useControlPanel.js';
import ControlPanelMenuToggle from '@/views/pages/control/ControlPanelMenuToggle.vue';
import PageControl from '@/views/pages/control/control-view.vue';

const props = defineProps({
  overlay: {
    type: Boolean,
    default: false,
  },
  fullscreen: {
    type: Boolean,
    default: false,
  },
  zIndex: {
    type: Number,
    default: 10050,
  },
});

const router = useRouter();
const controlPanelWindow = useControlPanelWindowStore();
const layout = useControlPanelLayoutStore();
const windowRef = ref(null);
const shellRef = computed(() => windowRef.value?.shellRef ?? null);

const sessionRect = computed(() => controlPanelWindow.rect);

const {updateShellWidth} = useControlPanelShellLayout(shellRef, computed(() => props.overlay));

function commitRect(rect) {
  controlPanelWindow.updateRect(fitControlPanelRectAboveDock(rect));
  updateShellWidth();
}

function focusWindow() {
  controlPanelWindow.focus();
}

onMounted(async () => {
  layout.bindViewport();
  layout.syncBreakpoints();
  await nextTick();
  updateShellWidth();
});

watch(
    () => controlPanelWindow.isVisible,
    async (visible) => {
      if (visible) {
        layout.syncBreakpoints();
        await nextTick();
        updateShellWidth();
      }
    },
);

watch(() => props.overlay, () => {
  updateShellWidth();
});

function closeWindow() {
  controlPanelWindow.close();

  if (isControlRoute(router.currentRoute.value)) {
    router.push({name: 'desktop'});
  }
}

function minimizeWindow() {
  const path = isControlRoute(router.currentRoute.value)
      ? router.currentRoute.value.path
      : controlPanelWindow.lastPath;

  controlPanelWindow.minimize(
      props.overlay ? 'floating' : 'fullscreen',
      path,
  );

  if (isControlRoute(router.currentRoute.value)) {
    router.push({name: 'desktop'});
  }
}

function toggleFloating() {
  if (props.overlay) {
    controlPanelWindow.openFullscreen();
    return;
  }

  controlPanelWindow.enterFloating();
}
</script>

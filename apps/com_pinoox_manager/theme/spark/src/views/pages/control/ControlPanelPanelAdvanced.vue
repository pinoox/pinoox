<template>
  <section
      class="appView controlPanelWindow"
      :class="{
        'is-floating': isFloating,
        'is-overlay': isFloating,
        'is-fullscreenPanel': isFullscreen,
      }"
      :style="panelStyle"
      @mousedown="onPanelFocus"
  >
    <div
        ref="shellRef"
        class="appView__shell"
        :class="{
          'is-floatingShell': isFloating,
          'is-interacting': interacting,
          'is-dragging': isDragging,
          'is-resizing': isResizing,
        }"
        :style="isFloating ? floatingStyle : undefined"
        @mousedown="onPanelFocus"
    >
      <header
          class="appView__toolbar"
          :class="{ 'is-draggable': isFloating }"
          @mousedown="onToolbarMouseDown"
      >
        <AppViewWindowChrome
            :floating="isFloating"
            @close="closeWindow"
            @minimize="minimizeWindow"
            @toggle-float="toggleFloating"
        />

        <ControlPanelMenuToggle v-if="layout.isCompact || layout.isMobile"/>

        <div class="appView__title">
          <Icon :is="saxIcon.control" class="appView__title-icon" size="sm"/>
          <span>کنترل پنل</span>
        </div>
      </header>

      <div
          class="controlPanelWindow__body @container"
          :class="{ 'is-interacting': interacting }"
      >
        <PageControl embedded/>
      </div>

      <div
          v-if="isFloating"
          class="appView__resizeHandle"
          title="تغییر اندازه"
          @mousedown="onResizeStart"
      />
    </div>
  </section>
</template>

<script setup>
import {computed, ref} from 'vue';
import {useRouter} from 'vue-router';
import {saxIcon} from '@/const/icons.js';
import Icon from '@/views/components/widgets/Icon.vue';
import {useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';
import {fitControlPanelRectAboveDock} from '@/stores/modules/controlPanelLayout.js';
import {useControlPanelLayoutStore} from '@/stores/modules/controlPanelLayout.js';
import {useAppViewFloating} from '@/views/composables/useAppViewFloating.js';
import {useControlPanelShellLayout} from '@/views/composables/useControlPanelShellLayout.js';
import {isControlRoute} from '@/views/composables/useControlPanel.js';
import AppViewWindowChrome from '@/views/pages/app-view/AppViewWindowChrome.vue';
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
const shellRef = ref(null);

const isFloating = computed(() => props.overlay);
const isFullscreen = computed(() => props.fullscreen);

const sessionRect = computed(() => controlPanelWindow.rect);

const panelStyle = computed(() => {
  if (!isFloating.value && !isFullscreen.value) {
    return {};
  }

  return {zIndex: props.zIndex};
});

const {updateShellWidth} = useControlPanelShellLayout(shellRef, isFloating);

const {
  shellStyle: floatingStyle,
  onDragStart,
  onResizeStart,
  interacting,
  isDragging,
  isResizing,
} = useAppViewFloating(shellRef, sessionRect, {
  onRectCommit: (rect) => {
    controlPanelWindow.updateRect(fitControlPanelRectAboveDock(rect));
    updateShellWidth();
  },
});

function onPanelFocus() {
  if (isFloating.value) {
    controlPanelWindow.focus();
  }
}

function onToolbarMouseDown(event) {
  if (!isFloating.value) {
    return;
  }

  controlPanelWindow.focus();
  onDragStart(event);
}

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
      isFloating.value ? 'floating' : 'fullscreen',
      path,
  );

  if (isControlRoute(router.currentRoute.value)) {
    router.push({name: 'desktop'});
  }
}

function toggleFloating() {
  if (isFloating.value) {
    controlPanelWindow.openFullscreen();
    return;
  }

  controlPanelWindow.enterFloating();
}
</script>

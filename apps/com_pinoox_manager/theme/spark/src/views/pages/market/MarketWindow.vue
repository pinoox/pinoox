<template>
  <ManagerWindow
      ref="windowRef"
      mode="auto"
      :root-class="advanced ? 'managerWindow' : 'managerSimple'"
      :toolbar-class="advanced ? 'managerWindow__toolbar' : 'managerSimple__toolbar'"
      :title-class="advanced ? 'managerWindow__title' : 'managerSimple__title'"
      :body-class="advanced ? 'managerWindow__body' : undefined"
      :frame-class="advanced ? undefined : 'managerSimple__frame'"
      close-aria-label="بستن مارکت"
      :overlay="overlay"
      :fullscreen="fullscreen"
      :z-index="zIndex"
      :session-rect="sessionRect"
      :shell-compact="layout.isCompact"
      :on-rect-commit="commitRect"
      :on-focus="actions.focusWindow"
      @close="actions.close"
      @minimize="onMinimize"
      @toggle-float="onToggleFloat"
  >
    <template #title>
      <Icon :is="saxIcon.market" class="appView__title-icon" size="sm"/>
      <span>مارکت پینوکس</span>
    </template>

    <MarketPanelShell/>
  </ManagerWindow>
</template>

<script setup>
import {computed, nextTick, onMounted, ref, watch} from 'vue';
import {saxIcon} from '@/const/icons.js';
import Icon from '@/views/components/widgets/Icon.vue';
import ManagerWindow from '@/views/components/layouts/ManagerWindow.vue';
import {useMarketWindowStore} from '@/stores/modules/marketWindow.js';
import {fitControlPanelRectAboveDock} from '@/stores/modules/controlPanelLayout.js';
import {useControlPanelLayoutStore} from '@/stores/modules/controlPanelLayout.js';
import {useControlPanelShellLayout} from '@/views/composables/useControlPanelShellLayout.js';
import {useManagerWindowActions} from '@/views/composables/useManagerWindowActions.js';
import {isMarketRoute} from '@/views/composables/useMarket.js';
import MarketPanelShell from '@/views/pages/market/MarketPanelShell.vue';

const props = defineProps({
  advanced: {
    type: Boolean,
    default: false,
  },
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

const marketWindow = useMarketWindowStore();
const layout = useControlPanelLayoutStore();
const windowRef = ref(null);
const shellRef = computed(() => windowRef.value?.shellRef ?? null);

const sessionRect = computed(() => (props.advanced ? marketWindow.rect : null));

const actions = useManagerWindowActions({
  windowStore: marketWindow,
  isRouteActive: isMarketRoute,
});

const {updateShellWidth} = useControlPanelShellLayout(
    shellRef,
    computed(() => props.advanced && props.overlay),
);

function commitRect(rect) {
  if (!props.advanced) {
    return;
  }

  marketWindow.updateRect(fitControlPanelRectAboveDock(rect));
  updateShellWidth();
}

function onMinimize() {
  actions.minimize(props.overlay);
}

function onToggleFloat() {
  actions.toggleFloat(props.overlay);
}

onMounted(async () => {
  if (!props.advanced) {
    return;
  }

  layout.bindViewport();
  layout.syncBreakpoints();
  await nextTick();
  updateShellWidth();
});

watch(
    () => marketWindow.isVisible,
    async (visible) => {
      if (!props.advanced || !visible) {
        return;
      }

      layout.syncBreakpoints();
      await nextTick();
      updateShellWidth();
    },
);

watch(() => props.overlay, () => {
  if (props.advanced) {
    updateShellWidth();
  }
});
</script>

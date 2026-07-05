<template>
  <Teleport to="body">
    <MarketWindow
        advanced
        v-show="marketWindow.isVisible"
        :overlay="marketWindow.mode === 'floating'"
        :fullscreen="marketWindow.mode === 'fullscreen'"
        :z-index="marketWindow.zIndex"
    />
  </Teleport>
</template>

<script setup>
import {watch} from 'vue';
import {useRoute} from 'vue-router';
import {useMarketWindowStore} from '@/stores/modules/marketWindow.js';
import {isMarketRoute} from '@/views/composables/useMarket.js';
import {useControlPanelLayoutStore} from '@/stores/modules/controlPanelLayout.js';
import {useAppViewMode} from '@/views/composables/useAppViewMode.js';
import MarketWindow from '@/views/pages/market/MarketWindow.vue';

const route = useRoute();
const marketWindow = useMarketWindowStore();
const layout = useControlPanelLayoutStore();
const {isSimple} = useAppViewMode();

function syncRouteSession() {
  if (isSimple.value || !isMarketRoute(route)) {
    return;
  }

  if (marketWindow.mode === 'minimized') {
    marketWindow.restoreSession();
    return;
  }

  if (marketWindow.mode === 'floating') {
    return;
  }

  if (marketWindow.mode === 'hidden') {
    marketWindow.openFullscreen();
  }
}

function syncMarketOnLeave() {
  if (!marketWindow.isOpen || marketWindow.isMinimized) {
    return;
  }

  const restoreMode = marketWindow.mode === 'floating' ? 'floating' : 'fullscreen';

  marketWindow.minimize(restoreMode, marketWindow.lastPath);
}

watch(
    () => route.path,
    () => {
      if (isSimple.value) {
        return;
      }

      if (isMarketRoute(route)) {
        marketWindow.setLastPath(route.path);
        syncRouteSession();
        return;
      }

      syncMarketOnLeave();
    },
    {immediate: true},
);

watch(isSimple, (simple) => {
  if (simple) {
    marketWindow.dismiss();
  }
});

watch(
    () => marketWindow.isVisible,
    (visible) => {
      if (visible) {
        layout.syncBreakpoints();
      }
    },
);

watch(() => layout.isMobile, () => {
  if (
      marketWindow.mode === 'floating'
      && !layout.frameWidthSource
  ) {
    marketWindow.syncFloatingRect();
  }
});
</script>

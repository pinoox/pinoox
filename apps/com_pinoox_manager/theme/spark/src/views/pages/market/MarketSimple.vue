<template>
  <ManagerWindow
      mode="auto"
      root-class="managerSimple marketSimple"
      toolbar-class="managerSimple__toolbar marketSimple__toolbar"
      title-class="managerSimple__title marketSimple__title"
      frame-class="managerSimple__frame marketSimple__frame"
      close-aria-label="بستن مارکت"
      @close="closeMarket"
  >
    <template #title>
      <Icon :is="saxIcon.market" class="appView__title-icon" size="sm"/>
      <span>مارکت پینوکس</span>
    </template>

    <MarketPanelShell/>
  </ManagerWindow>
</template>

<script setup>
import {saxIcon} from '@/const/icons.js';
import Icon from '@/views/components/widgets/Icon.vue';
import ManagerWindow from '@/views/components/layouts/ManagerWindow.vue';
import MarketPanelShell from '@/views/pages/market/MarketPanelShell.vue';
import {useMarket} from '@/views/composables/useMarket.js';

const {closeMarket} = useMarket();
</script>

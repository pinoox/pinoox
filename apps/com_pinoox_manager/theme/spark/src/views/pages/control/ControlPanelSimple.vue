<template>
  <section class="appView appView--simple controlPanelSimple">
    <div class="appView__toolbar">
      <button
          type="button"
          class="appView__back"
          aria-label="بستن کنترل پنل"
          @click="closeControlPanel"
      >
        بستن
      </button>

      <div class="appView__title">
        <Icon :is="saxIcon.control" class="appView__title-icon" size="sm"/>
        <span>کنترل پنل</span>
      </div>
    </div>

    <div class="appView__frame controlPanelSimple__frame">
      <div class="pageControl pageControl--embedded">
        <ControlSidebar class="pageControl__sidebar" embedded/>
        <div class="pageControl__page" :class="{'collapsed': sidebarStore.isCollapsed}">
          <RouterView/>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import {saxIcon} from '@/const/icons.js';
import Icon from '@/views/components/widgets/Icon.vue';
import ControlSidebar from '@/views/pages/control/control-sidebar.vue';
import {useSidebarStore} from '@/views/composables/useSidebar.js';
import {useControlPanel} from '@/views/composables/useControlPanel.js';

const sidebarStore = useSidebarStore();
const {closeControlPanel} = useControlPanel();
</script>

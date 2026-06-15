<template>
  <section class="appView appView--simple controlPanelSimple">
    <div class="appView__toolbar">
      <ControlPanelMenuToggle v-if="layout.isMobile"/>

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
      <div
          class="pageControl pageControl--embedded"
          :class="{
            'pageControl--mobile': layout.isMobile,
            'pageControl--mobileSidebarOpen': layout.mobileSidebarOpen,
            'pageControl--sidebarCollapsed': sidebarStore.isCollapsed && !layout.isMobile,
          }"
      >
        <Transition name="pageControlBackdrop">
          <button
              v-if="layout.isMobile && layout.mobileSidebarOpen"
              type="button"
              class="pageControl__mobileBackdrop"
              aria-label="بستن منو"
              @click="layout.closeMobileSidebar()"
          />
        </Transition>

        <ControlSidebar class="pageControl__sidebar" embedded/>
        <div class="pageControl__page" :class="{'collapsed': sidebarStore.isCollapsed && !layout.isMobile}">
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
import ControlPanelMenuToggle from '@/views/pages/control/ControlPanelMenuToggle.vue';
import {useSidebarStore} from '@/views/composables/useSidebar.js';
import {useControlPanel} from '@/views/composables/useControlPanel.js';
import {useControlPanelLayoutStore} from '@/stores/modules/controlPanelLayout.js';
import {onMounted, watch} from 'vue';
import {useRoute} from 'vue-router';

const sidebarStore = useSidebarStore();
const layout = useControlPanelLayoutStore();
const {closeControlPanel} = useControlPanel();
const route = useRoute();

onMounted(() => {
  layout.bindViewport();
});

watch(() => route.path, () => {
  layout.closeMobileSidebar();
});

watch(() => layout.isMobile, (mobile) => {
  if (mobile) {
    sidebarStore.setCollapsed(true);
    layout.closeMobileSidebar();
  }
});
</script>

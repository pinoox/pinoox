<template>
  <ControlSidebarCollapsed v-if="showCollapsedRail"/>

  <sidebar-menu
      v-else-if="showExpandedMenu"
      class="sidebar"
      :menu="menuItems"
      :collapsed="false"
      :rtl="true"
      width="300px"
      width-collapsed="72px"
      :show-toggle="!layout.isMobile"
      @update:collapsed="onToggleCollapse"
      @item-click="onItemClick"
  >
    <template #header>
      <div class="sidebar__header">
        <span class="sidebar__header-title">کنترل پنل</span>
        <button
            v-if="layout.isMobile"
            type="button"
            class="sidebar__mobileClose"
            aria-label="بستن منو"
            @click="layout.closeMobileSidebar()"
        >
          <LucideIcon :name="lucideSidebar.close" size="sm"/>
        </button>
      </div>
    </template>

    <template v-slot:toggle-icon>
      <LucideIcon :name="lucideSidebar.chevronRight" size="sm"/>
    </template>

    <template #icon>
      <LucideIcon :name="lucideSidebar.chevronLeft" size="sm"/>
    </template>
  </sidebar-menu>
</template>

<script setup>
import {computed, ref} from 'vue';
import {SidebarMenu} from 'vue-sidebar-menu';
import 'vue-sidebar-menu/dist/vue-sidebar-menu.css';
import {lucideSidebar} from '../../../const/icons.js';
import LucideIcon from '../../components/widgets/LucideIcon.vue';
import {useSidebarStore} from '../../composables/useSidebar.js';
import {useControlPanelLayoutStore} from '@/stores/modules/controlPanelLayout.js';
import {toSidebarMenuItems} from '@/views/pages/control/controlMenuItems.js';
import ControlSidebarCollapsed from '@/views/pages/control/control-sidebar-collapsed.vue';

defineProps({
  embedded: {
    type: Boolean,
    default: false,
  },
});

const sidebar = useSidebarStore();
const layout = useControlPanelLayoutStore();

const menuItems = ref(toSidebarMenuItems(LucideIcon));

const showCollapsedRail = computed(() => sidebar.isCollapsed && !layout.isMobile);
const showExpandedMenu = computed(() => !sidebar.isCollapsed || layout.mobileSidebarOpen);

const onToggleCollapse = (collapsed) => {
  sidebar.setCollapsed(collapsed);
};

const onItemClick = () => {
  layout.closeMobileSidebar();
};
</script>

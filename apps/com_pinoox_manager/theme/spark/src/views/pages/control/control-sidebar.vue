<template>
  <sidebar-menu
      v-if="showSidebar"
      class="sidebar"
      :menu="menuItems"
      :collapsed="isMenuCollapsed"
      :rtl="true"
      :relative="true"
      :disable-hover="true"
      width="300px"
      width-collapsed="72px"
      :hide-toggle="layout.isCompact"
      @update:collapsed="onToggleCollapse"
      @item-click="onItemClick"
  >
    <template #header>
      <div v-if="!isMenuCollapsed" class="sidebar__header">
        <span class="sidebar__header-title">کنترل پنل</span>
      </div>
    </template>

    <template v-slot:toggle-icon>
      <LucideIcon :name="isMenuCollapsed ? lucideSidebar.chevronLeft : lucideSidebar.chevronRight" size="sm"/>
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

defineProps({
  embedded: {
    type: Boolean,
    default: false,
  },
});

const sidebar = useSidebarStore();
const layout = useControlPanelLayoutStore();

const menuItems = ref(toSidebarMenuItems(LucideIcon));

const isMenuCollapsed = computed(() => sidebar.isCollapsed && !layout.isCompact);
const showSidebar = computed(() => !layout.isMobile || layout.mobileSidebarOpen);

const onToggleCollapse = (collapsed) => {
  if (layout.isCompact) {
    return;
  }

  sidebar.setCollapsed(collapsed);
};

const onItemClick = (event, item) => {
  if (!layout.isMobile) {
    return;
  }

  if (item?.child?.length) {
    return;
  }

  layout.closeMobileSidebar();
};
</script>

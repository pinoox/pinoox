<template>
  <ControlSidebarCollapsed v-if="sidebar.isCollapsed"/>

  <sidebar-menu
      v-else
      class="sidebar"
      :menu="menuItems"
      :collapsed="false"
      :rtl="true"
      width="300px"
      width-collapsed="72px"
      :show-toggle="true"
      @update:collapsed="onToggleCollapse"
  >
    <template #header>
      <div class="sidebar__header">
        <span class="sidebar__header-title">کنترل پنل</span>
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
import {ref} from 'vue';
import {SidebarMenu} from 'vue-sidebar-menu';
import 'vue-sidebar-menu/dist/vue-sidebar-menu.css';
import {lucideSidebar} from '../../../const/icons.js';
import LucideIcon from '../../components/widgets/LucideIcon.vue';
import {useSidebarStore} from '../../composables/useSidebar.js';
import {toSidebarMenuItems} from '@/views/pages/control/controlMenuItems.js';
import ControlSidebarCollapsed from '@/views/pages/control/control-sidebar-collapsed.vue';

defineProps({
  embedded: {
    type: Boolean,
    default: false,
  },
});

const sidebar = useSidebarStore();

const menuItems = ref(toSidebarMenuItems(LucideIcon));

const onToggleCollapse = (collapsed) => {
  sidebar.setCollapsed(collapsed);
};
</script>

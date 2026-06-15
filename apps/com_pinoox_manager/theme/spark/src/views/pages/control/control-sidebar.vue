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
      :link-component-name="embedded ? ControlPanelSidebarLink : undefined"
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
import {useRoute, useRouter} from 'vue-router';
import {SidebarMenu} from 'vue-sidebar-menu';
import 'vue-sidebar-menu/dist/vue-sidebar-menu.css';
import {lucideSidebar} from '../../../const/icons.js';
import LucideIcon from '../../components/widgets/LucideIcon.vue';
import {useSidebarStore} from '../../composables/useSidebar.js';
import {useControlPanelLayoutStore} from '@/stores/modules/controlPanelLayout.js';
import {toSidebarMenuItems} from '@/views/pages/control/controlMenuItems.js';
import ControlPanelSidebarLink from '@/views/pages/control/ControlPanelSidebarLink.vue';
import {
    isControlPanelMemoryPath,
    syncControlPanelMemoryRouter,
} from '@/router/controlPanelMemoryRouter.js';
import {useControlPanelWindowStore} from '@/stores/modules/controlPanelWindow.js';
import {isControlRoute} from '@/views/composables/useControlPanel.js';

const props = defineProps({
  embedded: {
    type: Boolean,
    default: false,
  },
});

const sidebar = useSidebarStore();
const layout = useControlPanelLayoutStore();
const controlPanelWindow = useControlPanelWindowStore();
const globalRoute = useRoute();
const globalRouter = useRouter();

const menuItems = ref(toSidebarMenuItems(LucideIcon));

const isMenuCollapsed = computed(() => sidebar.isCollapsed && !layout.isCompact);
const showSidebar = computed(() => !layout.isCompact || layout.mobileSidebarOpen);

const onToggleCollapse = (collapsed) => {
  if (layout.isCompact) {
    return;
  }

  sidebar.setCollapsed(collapsed);
};

const onItemClick = async (event, item) => {
  if (props.embedded && item?.href && isControlPanelMemoryPath(item.href)) {
    await syncControlPanelMemoryRouter(item.href);
    controlPanelWindow.setLastPath(item.href);

    if (isControlRoute(globalRoute)) {
      await globalRouter.push(item.href);
    }
  }

  if (!layout.isCompact) {
    return;
  }

  if (item?.child?.length) {
    return;
  }

  layout.closeMobileSidebar();
};
</script>

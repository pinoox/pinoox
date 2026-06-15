<template>
  <sidebar-menu
      class="sidebar"
      :menu="menuItems"
      :collapsed="sidebar.isCollapsed"
      :rtl="true"
      :relative="true"
      width="300px"
      width-collapsed="72px"
      :show-toggle="true"
      @update:collapsed="onToggleCollapse"
  >
    <template #header>
      <div class="sidebar__header" :class="{ 'is-collapsed': sidebar.isCollapsed }">
        <span v-if="!sidebar.isCollapsed" class="sidebar__header-title">کنترل پنل</span>
        <button
            type="button"
            class="sidebar__close"
            aria-label="بستن کنترل پنل"
            @click="closeControlPanel"
        >
          <LucideIcon :name="lucideSidebar.close" size="sm"/>
        </button>
      </div>
    </template>

    <template v-slot:toggle-icon>
      <LucideIcon :name="sidebar.isCollapsed ? lucideSidebar.chevronLeft : lucideSidebar.chevronRight" size="sm"/>
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
import {lucideSidebar} from "../../../const/icons.js";
import LucideIcon from "../../components/widgets/LucideIcon.vue";
import {useSidebarStore} from "../../composables/useSidebar.js";
import {useControlPanel} from "../../composables/useControlPanel.js";

defineProps({
  embedded: {
    type: Boolean,
    default: false,
  },
});

const sidebar = useSidebarStore();
const {closeControlPanel: dismissControlPanel} = useControlPanel();

const onToggleCollapse = (collapsed) => {
  sidebar.setCollapsed(collapsed);
};

const closeControlPanel = () => {
  dismissControlPanel();
};

const menuIcon = (name) => ({
  element: LucideIcon,
  attributes: { name },
});

const menuLink = (item) => ({
  ...item,
  icon: menuIcon(item.iconName),
  attributes: {'aria-label': item.title},
});

const menuItems = ref([
  menuLink({
    href: '/control/widgets',
    title: 'ویجت‌ها',
    iconName: lucideSidebar.widgets,
  }),
  menuLink({
    href: '/control/apps',
    title: 'اپلیکیشن‌ها',
    iconName: lucideSidebar.apps,
  }),
  menuLink({
    href: '/control/apps/manual',
    title: 'نصب دستی',
    iconName: lucideSidebar.upload,
  }),
  menuLink({
    href: '/control/routes',
    title: 'مسیریابی',
    iconName: lucideSidebar.routes,
  }),
  {
    title: 'تنظیمات',
    icon: menuIcon(lucideSidebar.setting),
    attributes: {'aria-label': 'تنظیمات'},
    child: [
      {
        href: '/control/settings/appearance',
        title: 'ظاهر و زمینه',
      },
      {
        href: '/control/settings/application',
        title: 'تنظیمات اپلیکیشن',
      },
    ],
  },
  menuLink({
    href: '/control/profile',
    title: 'حساب کاربری',
    iconName: lucideSidebar.profile,
  }),
  menuLink({
    href: '/control/pincore',
    title: 'پینوکس',
    iconName: lucideSidebar.pincore,
  }),
  menuLink({
    href: '/market',
    title: 'مارکت',
    iconName: lucideSidebar.market,
  }),
]);
</script>

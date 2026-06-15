<template>
  <sidebar-menu
      class="sidebar"
      :menu="menuItems"
      :collapsed="sidebar.isCollapsed"
      :rtl="true"
      width="300px"
      :show-toggle="true"
      @update:collapsed="onToggleCollapse"
  >
    <template v-slot:toggle-icon>
      <Icon :is="sidebar.isCollapsed ? saxIcon.arrowLeft : saxIcon.arrowRight" size="sm"/>
    </template>

    <template #icon>
      <Icon :is="saxIcon.arrowLeft" size="sm"/>
    </template>

  </sidebar-menu>
</template>

<script setup>
import {ref} from 'vue';
import {SidebarMenu} from 'vue-sidebar-menu';
import 'vue-sidebar-menu/dist/vue-sidebar-menu.css';
import {saxIcon as icons, saxIcon} from "../../../const/icons.js";
import Icon from "../../components/widgets/Icon.vue";
import {useSidebarStore} from "../../composables/useSidebar.js";

const sidebar = useSidebarStore();

const onToggleCollapse = (collapsed) => {
  sidebar.setCollapsed(collapsed);
};

const menuItems = ref([
  {
    header: 'کنترل پنل',
    hiddenOnCollapse: true,
  },
  {
    href: '/',
    title: 'برگشت',
    class: 'back',
    icon: {
      element: Icon,
      attributes: { is: icons.back },
    },
  },
  {
    href: '/control/widgets',
    title: 'ویجت‌ها',
    icon: {
      element: Icon,
      attributes: { is: icons.widgets },
    },
  },
  {
    href: '/control/apps',
    title: 'اپلیکیشن‌ها',
    icon: {
      element: Icon,
      attributes: { is: icons.apps },
    },
  },
  {
    href: '/control/apps/manual',
    title: 'نصب دستی',
    icon: {
      element: Icon,
      attributes: { is: icons.upload },
    },
  },
  {
    href: '/control/routes',
    title: 'مسیریابی',
    icon: {
      element: Icon,
      attributes: { is: icons.routes },
    },
  },
  {
    title: 'تنظیمات',
    icon: {
      element: Icon,
      attributes: { is: icons.setting },
    },
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
  {
    href: '/control/profile',
    title: 'حساب کاربری',
    icon: {
      element: Icon,
      attributes: { is: icons.profile },
    },
  },
  {
    href: '/control/pincore',
    title: 'پینوکس',
    icon: {
      element: Icon,
      attributes: { is: icons.pincore },
    },
  },
  {
    href: '/market',
    title: 'مارکت',
    icon: {
      element: Icon,
      attributes: { is: icons.market },
    },
  },
]);
</script>

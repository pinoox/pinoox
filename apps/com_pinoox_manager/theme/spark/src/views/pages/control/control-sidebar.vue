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
import {useRouter} from 'vue-router';
import {SidebarMenu} from 'vue-sidebar-menu';
import 'vue-sidebar-menu/dist/vue-sidebar-menu.css';
import {lucideSidebar} from "../../../const/icons.js";
import LucideIcon from "../../components/widgets/LucideIcon.vue";
import {useSidebarStore} from "../../composables/useSidebar.js";

const router = useRouter();
const sidebar = useSidebarStore();

const onToggleCollapse = (collapsed) => {
  sidebar.setCollapsed(collapsed);
};

const closeControlPanel = () => {
  router.push('/');
};

const menuIcon = (name) => ({
  element: LucideIcon,
  attributes: { name },
});

const menuItems = ref([
  {
    href: '/control/widgets',
    title: 'ویجت‌ها',
    icon: menuIcon(lucideSidebar.widgets),
  },
  {
    href: '/control/apps',
    title: 'اپلیکیشن‌ها',
    icon: menuIcon(lucideSidebar.apps),
  },
  {
    href: '/control/apps/manual',
    title: 'نصب دستی',
    icon: menuIcon(lucideSidebar.upload),
  },
  {
    href: '/control/routes',
    title: 'مسیریابی',
    icon: menuIcon(lucideSidebar.routes),
  },
  {
    title: 'تنظیمات',
    icon: menuIcon(lucideSidebar.setting),
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
    icon: menuIcon(lucideSidebar.profile),
  },
  {
    href: '/control/pincore',
    title: 'پینوکس',
    icon: menuIcon(lucideSidebar.pincore),
  },
  {
    href: '/market',
    title: 'مارکت',
    icon: menuIcon(lucideSidebar.market),
  },
]);
</script>

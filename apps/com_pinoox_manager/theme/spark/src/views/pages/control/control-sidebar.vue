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
      <Icon :is="sidebar.isCollapsed ? saxIcon.arrowLeft : saxIcon.arrowRight"/>
    </template>

    <template #icon>
      <Icon :is="saxIcon.arrowLeft"/>
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
      attributes: {
        is: icons.back
      }
    }
  },
  {
    href: '/control/apps',
    title: 'اپلکیشن ها',
    icon: {
      element: Icon,
      attributes: {
        is: icons.apps
      }
    }
  },
  {
    href: '/control/routes',
    title: 'مسیریابی',
    icon: {
      element: Icon,
      attributes: {
        is: icons.routes
      }
    }
  },
  {
    href: '/control/appearance',
    title: 'ظاهری',
    icon: {
      element: Icon,
      attributes: {
        is: icons.appearance
      }
    }
  },
  {
    href: '/control/profile',
    title: 'حساب کاربری',
    disabled: true,
    badge: {
      text: 'به‌زودی',
      class: 'vsm--badge_default'
    },
    icon: {
      element: Icon,
      attributes: {
        is: icons.profile
      }
    }
  },
  {
    href: '/control/pincore',
    title: 'پینوکس',
    disabled: true,
    badge: {
      text: 'به‌زودی',
      class: 'vsm--badge_default'
    },
    icon: {
      element: Icon,
      attributes: {
        is: icons.pincore
      }
    }
  },
]);
</script>


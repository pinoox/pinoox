<template>
  <sidebar-menu
      class="sidebar"
      :menu="menuItems"
      :collapsed="isCollapsed"
      :rtl="true"
      width="300px"
      :show-toggle="true"
      @toggle-collapse="toggleSidebar"
  >
    <template v-slot:toggle-icon>
      <Icon :is="isCollapsed ? saxIcon.arrowLeft : saxIcon.arrowRight"/>
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

const isCollapsed = ref(false);

const toggleSidebar = (collapsed) => {
  isCollapsed.value = collapsed;
};

const menuItems = ref([
  {
    href: '/calendar',
    title: 'حساب کاربری',
    icon: {
      element: Icon,
      attributes: {
        is: icons.profile
      }
    }
  },
  {
    href: '/calendar',
    title: 'ظاهری',
    icon: {
      element: Icon,
      attributes: {
        is: icons.appearance
      }
    }
  },
  {
    href: '/clients',
    title: 'اپلکیشن ها',
    icon: {
      element: Icon,
      attributes: {
        is: icons.apps
      }
    }
  },
  {
    href: '/animals',
    title: 'مسیریابی',
    icon: {
      element: Icon,
      attributes: {
        is: icons.routes
      }
    }
  },
  {
    href: '/stocks',
    title: 'پینوکس',
    icon: {
      element: Icon,
      attributes: {
        is: icons.pincore
      }
    }
  },
]);
</script>


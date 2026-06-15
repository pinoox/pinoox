<template>
  <div class="pageControl" :class="{ 'pageControl--embedded': embedded }">
    <ControlSidebar class="pageControl__sidebar" :embedded="embedded"/>
    <div class="pageControl__page" :class="{'collapsed':sidebarStore.isCollapsed}">
      <RouterView/>
    </div>
  </div>
</template>

<script setup>
import ControlSidebar from "./control-sidebar.vue";
import {useSidebarStore} from "../../composables/useSidebar.js";

defineProps({
  embedded: {
    type: Boolean,
    default: false,
  },
});

const sidebarStore = useSidebarStore();

</script>

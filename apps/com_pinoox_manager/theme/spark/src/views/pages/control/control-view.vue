<template>
  <div class="pageControl">
    <ControlSidebar class="pageControl__sidebar"/>
    <div class="pageControl__page" :class="{'collapsed':sidebarStore.isCollapsed}">
      <RouterView/>
    </div>
  </div>
</template>

<script setup>
import ControlSidebar from "./control-sidebar.vue";
import {useSidebarStore} from "../../composables/useSidebar.js";

const sidebarStore = useSidebarStore();

</script>

<template>
  <div :style="{ backgroundImage: `url(${selectedBackground})` }"
       class="w-full h-screen bg-cover bg-center">
    <Toolbar v-if="hasToolbar"/>
    <RouterView/>
  </div>

  <ModalTarget/>
</template>

<script setup>
import {useBackground} from "./views/composables/useBackground.js";
import {useRouteMeta} from "@views/composables/useRouteMeta.js";

const {selectedBackground} = useBackground();
const {hasToolbar} = useRouteMeta();

</script>
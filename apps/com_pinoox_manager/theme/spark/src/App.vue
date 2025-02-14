<template>
  <div :style="{ backgroundImage: `url(${selectedBackground})` }"
       class="w-full h-screen bg-cover bg-center">
    <Toolbar/>
    <RouterView/>
  </div>

  <ModalTarget />
</template>

<script setup>
import {useBackground} from "./views/composables/useBackground.js";

const { selectedBackground } = useBackground();

</script>
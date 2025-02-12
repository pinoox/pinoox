<template>
  <div :style="{ backgroundImage: `url(${selectedBackground})` }"
       class="w-full h-screen bg-cover bg-center">
    <Toolbar/>
    <RouterView/>
  </div>
</template>

<script setup>
import {onMounted, ref} from "vue";
import {useBackground} from "./views/composables/useBackground.js";

const { backgrounds, selectedBackground, changeBackground } = useBackground();

</script>
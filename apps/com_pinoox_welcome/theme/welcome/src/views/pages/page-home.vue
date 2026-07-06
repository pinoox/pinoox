<template>
  <main class="page">
    <Hero></Hero>
    <Market></Market>
    <Developer></Developer>
  </main>
</template>

<script>


import Hero from "@/views/sections/hero.vue";
import Market from "@/views/sections/market.vue";
import Developer from "@/views/sections/developer.vue";

export default {
  components: { Developer, Market, Hero }

};
</script>
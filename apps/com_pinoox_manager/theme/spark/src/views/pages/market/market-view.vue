<template>
  <MarketSimple v-if="isSimple"/>
</template>

<script setup>
import {useAppViewMode} from '@/views/composables/useAppViewMode.js';
import MarketSimple from '@/views/pages/market/MarketSimple.vue';

const {isSimple} = useAppViewMode();
</script>

<template>
  <MarketWindow v-if="isSimple"/>
</template>

<script setup>
import {useAppViewMode} from '@/views/composables/useAppViewMode.js';
import MarketWindow from '@/views/pages/market/MarketWindow.vue';

const {isSimple} = useAppViewMode();
</script>

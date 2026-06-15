<template>
  <ControlPanelSimple v-if="isSimple"/>
</template>

<script setup>
import {useAppViewMode} from '@/views/composables/useAppViewMode.js';
import ControlPanelSimple from '@/views/pages/control/ControlPanelSimple.vue';

const {isSimple} = useAppViewMode();
</script>

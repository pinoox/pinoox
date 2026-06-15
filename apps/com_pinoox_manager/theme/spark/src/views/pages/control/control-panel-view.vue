<template>
  <ControlPanelSimple v-if="isSimple"/>
  <PageControl v-else embedded/>
</template>

<script setup>
import {useAppViewMode} from '@/views/composables/useAppViewMode.js';
import ControlPanelSimple from '@/views/pages/control/ControlPanelSimple.vue';
import PageControl from '@/views/pages/control/control-view.vue';

const {isSimple} = useAppViewMode();
</script>

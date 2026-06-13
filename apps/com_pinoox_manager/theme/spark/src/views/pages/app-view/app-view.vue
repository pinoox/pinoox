<template>
  <AppViewSimple v-if="isSimple" :package_name="package_name"/>
</template>

<script setup>
import {useAppViewMode} from '@/views/composables/useAppViewMode.js';
import AppViewSimple from '@/views/pages/app-view/AppViewSimple.vue';

defineProps({
  package_name: {
    type: String,
    required: true,
  },
});

const {isSimple} = useAppViewMode();
</script>

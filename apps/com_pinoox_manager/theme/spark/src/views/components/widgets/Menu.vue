<template>
  <div class="toolbarMenu">
    <Icon :is="icon" class="toolbarMenu__icon"/>
    <span class="toolbarMenu__label">{{ label }}</span>
  </div>
</template>

<script setup>

const props = defineProps({
  icon: {
    type: String,
    default: null,
  },
  label: {
    type: String,
    required: true,
  },
});
</script>
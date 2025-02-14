<template>
  <button
      :class="[
      'btn',
      `btn-${variant}`,
      sizeClass,
      { 'btn-loading': isLoading, 'btn-disabled': isDisabled || isLoading }
    ]"
      :disabled="isDisabled || isLoading"
      @click="handleClick"
  >
    <span v-if="isLoading" class="spinner"></span>
    <slot v-else>{{ label }}</slot>
  </button>
</template>

<script setup>
import { defineProps, defineEmits, computed } from 'vue';

const props = defineProps({
  label: {
    type: String,
    default: 'Button',
  },
  variant: {
    type: String,
    default: 'primary', // e.g., 'primary', 'secondary', 'danger', etc.
  },
  size: {
    type: String,
    default: 'md', // e.g., 'sm', 'md', 'lg'
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  isDisabled: {
    type: Boolean,
    default: false,
  },
});

const emits = defineEmits(['click']);

const sizeClass = computed(() => {
  return {
    sm: 'btn-sm',
    md: 'btn-md',
    lg: 'btn-lg',
  }[props.size] || 'btn-md';
});

const handleClick = (event) => {
  if (!props.isDisabled && !props.isLoading) {
    emits('click', event);
  }
};
</script>

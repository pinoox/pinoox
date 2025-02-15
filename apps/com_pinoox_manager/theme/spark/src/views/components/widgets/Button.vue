<template>
  <button
      :class="[
      'btn',
      sizeClass,
      outline ? `btn-outline-${variant}` : `btn-${variant}`, // Conditional class for outline
      {
        'btn-loading': isLoading,
        'btn-disabled': isDisabled || isLoading,
        'btn-full': fullWidth
      }
    ]"
      :disabled="isDisabled || isLoading"
      @click="handleClick"
  >
    <span v-if="isLoading" class="spinner"></span>
    <slot v-else>{{ label }}</slot>
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: {
    type: String,
    default: 'Button',
  },
  variant: {
    type: String,
    default: 'primary', // e.g., 'primary', 'secondary', 'danger', 'warning', 'accent', 'light', 'dark'
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
  fullWidth: {
    type: Boolean,
    default: false, // New prop to make button 100% width
  },
  outline: {
    type: Boolean,
    default: false, // If true, use outline variant instead of solid
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
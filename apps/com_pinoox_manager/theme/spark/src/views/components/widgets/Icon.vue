<template>
  <span class="icon" :class="sizeClass" :style="iconStyle">
    <component :is="is"/>
  </span>
</template>

<script setup>
import {computed} from 'vue';

const SIZE_MAP = {
  xs: '0.875rem',
  sm: '1rem',
  md: '1.25rem',
  lg: '1.5rem',
  xl: '1.75rem',
  '2xl': '2rem',
};

const props = defineProps({
  is: {},
  size: {
    type: [String, Number],
    default: 'md',
  },
});

const sizeClass = computed(() => {
  if (props.size in SIZE_MAP)
    return `icon--${props.size}`;

  return null;
});

const iconStyle = computed(() => {
  if (props.size in SIZE_MAP)
    return null;

  const value = typeof props.size === 'number' ? `${props.size}px` : props.size;

  if (typeof value === 'string' && /^\d/.test(value))
    return {'--icon-size': value};

  return null;
});
</script>

<style scoped>
.icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: var(--icon-size, 1.25rem);
  height: var(--icon-size, 1.25rem);
  color: currentColor;
  line-height: 0;
  vertical-align: middle;
}

.icon :deep(svg) {
  width: 100%;
  height: 100%;
  display: block;
}
</style>

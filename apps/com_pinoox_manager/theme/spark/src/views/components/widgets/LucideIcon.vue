<template>
  <span class="icon lucideIcon" :class="sizeClass" :style="iconStyle">
    <component
        v-if="component"
        :is="component"
        :size="sizePx"
        :stroke-width="strokeWidth"
        color="currentColor"
        absolute-stroke-width
    />
  </span>
</template>

<script setup>
import { computed } from 'vue';
import { lucideIconSize, lucideStrokeWidth, resolveLucideComponent } from '@/utils/lucideIcon.js';

const SIZE_MAP = {
  xs: '0.875rem',
  sm: '1rem',
  md: '1.25rem',
  lg: '1.5rem',
  xl: '1.75rem',
  '2xl': '2rem',
};

const props = defineProps({
  name: {
    type: String,
    required: true,
  },
  size: {
    type: [String, Number],
    default: 'sm',
  },
});

const component = computed(() => resolveLucideComponent(props.name));

const sizeClass = computed(() => {
  if (props.size in SIZE_MAP) {
    return `icon--${props.size}`;
  }

  return null;
});

const iconStyle = computed(() => {
  if (props.size in SIZE_MAP) {
    return null;
  }

  const value = typeof props.size === 'number' ? `${props.size}px` : props.size;

  if (typeof value === 'string' && /^\d/.test(value)) {
    return { '--icon-size': value };
  }

  return null;
});

const sizePx = computed(() => {
  if (props.size in SIZE_MAP) {
    return lucideIconSize(props.size);
  }

  const numeric = Number.parseInt(String(props.size), 10);

  return Number.isFinite(numeric) ? numeric : lucideIconSize('sm');
});

const strokeWidth = computed(() => lucideStrokeWidth(props.size in SIZE_MAP ? props.size : 'sm'));
</script>

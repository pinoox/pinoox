<template>
  <span class="appBrandIcon" :class="sizeClass">
    <img :src="pinooxLogo" :alt="alt" class="appBrandIcon__logo" draggable="false"/>
    <span class="appBrandIcon__badge" aria-hidden="true">
      <AppIcon
          :lucide="lucide"
          :colors="colors"
          icon-style="gradient"
          icon-source="lucide"
          size="xs"
      />
    </span>
  </span>
</template>

<script setup>
import { computed } from 'vue';
import pinooxLogo from '@/assets/media/pinoox/logo.png';
import { PINOOX_ICON_GRADIENT } from '@/const/pinooxBrand.js';

const SIZE_MAP = {
  sm: 'appBrandIcon--sm',
  md: 'appBrandIcon--md',
  lg: 'appBrandIcon--lg',
};

const props = defineProps({
  lucide: {
    type: String,
    default: 'settings',
  },
  colors: {
    type: Array,
    default: () => PINOOX_ICON_GRADIENT,
  },
  size: {
    type: String,
    default: 'md',
  },
  alt: {
    type: String,
    default: '',
  },
});

const sizeClass = computed(() => SIZE_MAP[props.size] ?? SIZE_MAP.md);
</script>

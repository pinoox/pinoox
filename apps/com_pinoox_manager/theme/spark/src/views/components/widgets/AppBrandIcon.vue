<template>
  <span class="appBrandIcon" :class="sizeClass">
    <AppIcon
        :lucide="lucide"
        :colors="colors"
        icon-style="gradient"
        icon-source="lucide"
        :size="size"
        :alt="alt"
    />
    <span class="appBrandIcon__badge" aria-hidden="true">
      <img :src="pinooxLogo" alt="" class="appBrandIcon__logo" draggable="false"/>
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
    default: 'layout-dashboard',
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

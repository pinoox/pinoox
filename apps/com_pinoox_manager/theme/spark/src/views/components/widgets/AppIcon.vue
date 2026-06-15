<template>

  <span

      class="appIcon"

      :class="[sizeClass, variantClass, {
        'appIcon--has-glyph': showGlyph,
        'appIcon--has-lucide': showLucide,
        'appIcon--lucide-crystal': showLucide && isLucideCrystal,
        'appIcon--lucide-gradient': showLucide && isLucideGradient,
        'appIcon--custom-image': isCustomImage,
      }]"

      :style="rootStyle"

  >

    <img

        v-if="showImage"

        :src="src"

        :alt="alt"

        class="appIcon__image"

        loading="lazy"

        draggable="false"

    />

    <span v-else-if="showLucide" class="appIcon__lucide" aria-hidden="true">
      <component
          :is="lucideComponent"
          :size="lucideSizePx"
          :color="lucideIconColor"
          :stroke-width="lucideStrokeWidth"
          absolute-stroke-width
      />
    </span>

    <span v-else-if="showGlyph" class="appIcon__glyph" aria-hidden="true">

      <Icon :is="glyph" :size="glyphIconSize"/>

    </span>

    <span v-else class="appIcon__fallback" aria-hidden="true">

      <Icon :is="saxIcon.apps" :size="fallbackIconSize"/>

    </span>

  </span>

</template>



<script setup>

import {computed} from 'vue';

import Icon from '@/views/components/widgets/Icon.vue';

import {saxIcon} from '@/const/icons.js';

import {lucideIconSize, resolveLucideComponent} from '@/utils/lucideIcon.js';



const SIZE_MAP = {

  xs: '1.25rem',

  sm: '1.75rem',

  md: '4rem',

  lg: '5rem',

  xl: '5.5rem',

  tray: '3.25rem',

};



const FALLBACK_ICON_SIZE = {

  xs: 'xs',

  sm: 'sm',

  md: 'lg',

  lg: 'xl',

  xl: 'xl',

  tray: 'md',

  dock: 'sm',

};



const GLYPH_ICON_SIZE = {

  xs: 'xs',

  sm: 'xs',

  md: 'lg',

  lg: 'xl',

  xl: 'xl',

  tray: 'sm',

  dock: 'sm',

};



const props = defineProps({

  src: {

    type: String,

    default: '',

  },

  lucide: {

    type: String,

    default: '',

  },

  colors: {

    type: Array,

    default: () => [],

  },

  iconSource: {

    type: String,

    default: '',

  },

  iconStyle: {

    type: String,

    default: 'crystal',

  },

  glyph: {

    type: [Object, Function],

    default: null,

  },

  alt: {

    type: String,

    default: '',

  },

  size: {

    type: String,

    default: 'md',

    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl', 'tray', 'dock'].includes(value),

  },

  variant: {

    type: String,

    default: 'frame',

    validator: (value) => ['frame', 'dock', 'soft'].includes(value),

  },

});



const lucideComponent = computed(() => resolveLucideComponent(props.lucide));

const showLucide = computed(() => props.iconSource !== 'custom' && Boolean(props.lucide) && Boolean(lucideComponent.value));

const showImage = computed(() => props.iconSource === 'custom' && Boolean(props.src));

const showGlyph = computed(() => !showLucide.value && !showImage.value && Boolean(props.glyph));

const isCustomImage = computed(() => showImage.value);

const isLucideGradient = computed(() => props.iconStyle === 'gradient');

const isLucideCrystal = computed(() => !isLucideGradient.value);

const sizeClass = computed(() => `appIcon--${props.size}`);

const variantClass = computed(() => `appIcon--${props.variant}`);

const lucideSizePx = computed(() => lucideIconSize(props.size));

const lucideStrokeWidth = computed(() => {
  const map = {
    xs: 2.15,
    sm: 2.15,
    md: 2.35,
    lg: 2.4,
    xl: 2.4,
    tray: 2.2,
    dock: 2.2,
  };

  return map[props.size] ?? 2.35;
});

const lucideIconColor = computed(() => '#ffffff');

const lucideColors = computed(() => {
  const colors = Array.isArray(props.colors) ? props.colors.filter(Boolean) : [];

  return {
    c0: colors[0] ?? '#a9492e',
    c1: colors[1] ?? colors[0] ?? '#c45c3e',
    c2: colors[2] ?? colors[1] ?? '#8b3a24',
  };
});

const rootStyle = computed(() => {
  const style = {};

  if (props.size !== 'dock') {
    const value = SIZE_MAP[props.size];

    if (value) {
      style['--app-icon-size'] = value;
    }
  }

  if (showLucide.value && isLucideGradient.value) {
    style['--icon-c0'] = lucideColors.value.c0;
    style['--icon-c1'] = lucideColors.value.c1;
    style['--icon-c2'] = lucideColors.value.c2;
  }

  return Object.keys(style).length ? style : null;
});



const fallbackIconSize = computed(() => FALLBACK_ICON_SIZE[props.size] || 'md');

const glyphIconSize = computed(() => GLYPH_ICON_SIZE[props.size] || 'sm');

</script>



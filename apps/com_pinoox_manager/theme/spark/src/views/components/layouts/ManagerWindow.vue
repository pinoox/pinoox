<template>
  <section
      v-if="isSimpleChrome"
      class="appView appView--simple"
      :class="rootClass"
  >
    <div class="appView__toolbar" :class="toolbarClass">
      <button
          type="button"
          class="appView__back"
          :aria-label="closeAriaLabel"
          @click="$emit('close')"
      >
        {{ closeLabel }}
      </button>

      <slot name="toolbar-before"/>

      <div v-if="$slots.title" class="appView__title" :class="titleClass">
        <slot name="title"/>
      </div>

      <slot name="toolbar-after"/>
    </div>

    <div class="appView__frame" :class="frameClass">
      <slot/>
    </div>
  </section>

  <section
      v-else
      class="appView"
      :class="[
        rootClass,
        {
          'is-floating': isFloating,
          'is-overlay': isFloating,
          'is-fullscreenPanel': isFullscreen,
        },
      ]"
      :style="panelStyle"
      @mousedown="onPanelFocus"
  >
    <div
        ref="shellRef"
        class="appView__shell"
        :class="{
          'is-floatingShell': isFloating,
          'is-compact': shellCompact,
          'is-interacting': interacting,
          'is-dragging': isDragging,
          'is-resizing': isResizing,
        }"
        :style="isFloating ? floatingStyle : undefined"
        @mousedown="onPanelFocus"
    >
      <header
          class="appView__toolbar"
          :class="[toolbarClass, { 'is-draggable': isFloating }]"
          @mousedown="onToolbarMouseDown"
      >
        <AppViewWindowChrome
            :floating="isFloating"
            @close="$emit('close')"
            @minimize="$emit('minimize')"
            @toggle-float="$emit('toggle-float')"
        />

        <slot name="toolbar-before"/>

        <div v-if="$slots.title" class="appView__title" :class="titleClass">
          <slot name="title"/>
        </div>

        <slot name="toolbar-after"/>
      </header>

      <slot name="before-body"/>

      <div
          v-if="bodyClass"
          :class="[bodyClass, { 'is-interacting': interacting }]"
      >
        <slot/>
      </div>
      <slot v-else/>

      <slot name="footer"/>

      <div
          v-if="isFloating"
          class="appView__resizeHandle"
          title="تغییر اندازه"
          @mousedown="onResizeStart"
      />
    </div>
  </section>
</template>

<script setup>
import {computed, ref, toRef} from 'vue';
import AppViewWindowChrome from '@/views/pages/app-view/AppViewWindowChrome.vue';
import {useAppViewMode} from '@/views/composables/useAppViewMode.js';
import {useManagerWindowFloating} from '@/views/composables/useManagerWindowFloating.js';

const props = defineProps({
  /**
   * auto: simple → close button, advanced → minimize/close/float chrome
   * simple | advanced: force toolbar chrome regardless of app view mode
   */
  mode: {
    type: String,
    default: 'auto',
    validator: (value) => ['auto', 'simple', 'advanced'].includes(value),
  },
  simple: {
    type: Boolean,
    default: undefined,
  },
  overlay: {
    type: Boolean,
    default: false,
  },
  fullscreen: {
    type: Boolean,
    default: false,
  },
  zIndex: {
    type: Number,
    default: 10050,
  },
  rootClass: {
    type: [String, Array, Object],
    default: '',
  },
  toolbarClass: {
    type: [String, Array, Object],
    default: '',
  },
  titleClass: {
    type: [String, Array, Object],
    default: '',
  },
  frameClass: {
    type: [String, Array, Object],
    default: '',
  },
  bodyClass: {
    type: [String, Array, Object],
    default: '',
  },
  shellCompact: {
    type: Boolean,
    default: false,
  },
  sessionRect: {
    type: Object,
    default: null,
  },
  closeLabel: {
    type: String,
    default: 'بستن',
  },
  closeAriaLabel: {
    type: String,
    default: 'بستن',
  },
  onRectCommit: {
    type: Function,
    default: null,
  },
  onFocus: {
    type: Function,
    default: null,
  },
  onInteract: {
    type: Function,
    default: null,
  },
});

defineEmits(['close', 'minimize', 'toggle-float']);

const {isSimple: isAppSimple} = useAppViewMode();

const isSimpleChrome = computed(() => {
  if (props.simple === true) {
    return true;
  }

  if (props.simple === false) {
    return false;
  }

  if (props.mode === 'simple') {
    return true;
  }

  if (props.mode === 'advanced') {
    return false;
  }

  return isAppSimple.value;
});

const shellRef = ref(null);

const {
  isFloating,
  isFullscreen,
  panelStyle,
  floatingStyle,
  interacting,
  isDragging,
  isResizing,
  onPanelFocus,
  onToolbarMouseDown,
  onResizeStart,
} = useManagerWindowFloating({
  shellRef,
  overlay: toRef(props, 'overlay'),
  fullscreen: toRef(props, 'fullscreen'),
  zIndex: toRef(props, 'zIndex'),
  sessionRect: toRef(props, 'sessionRect'),
  onRectCommit: (...args) => props.onRectCommit?.(...args),
  onFocus: () => props.onFocus?.(),
  onInteract: (...args) => props.onInteract?.(...args),
});

defineExpose({
  shellRef,
  isSimpleChrome,
});
</script>

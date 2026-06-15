<template>
  <div
      class="appView__browse"
      :class="{
        'appView__browse--navOnly': showNav && !showAddress,
        'appView__browse--addressOnly': showAddress && !showNav,
      }"
      dir="ltr"
  >
    <div v-if="showNav" class="appView__nav">
      <button
          type="button"
          class="appView__toolBtn"
          :disabled="!canGoBack || loading"
          title="صفحه قبل"
          @click="$emit('goBack')"
      >
        <Icon :is="saxIcon.arrowLeft" class="appView__toolIcon" size="sm"/>
      </button>

      <button
          type="button"
          class="appView__toolBtn"
          :disabled="!canGoForward || loading"
          title="صفحه بعد"
          @click="$emit('goForward')"
      >
        <Icon :is="saxIcon.arrowRight" class="appView__toolIcon" size="sm"/>
      </button>

      <button
          type="button"
          class="appView__toolBtn"
          :disabled="loading"
          title="بازآوری صفحه"
          @click="$emit('reload')"
      >
        <Icon :is="saxIcon.refresh" class="appView__toolIcon" size="sm" :class="{ 'is-spinning': loading }"/>
      </button>

      <button
          v-if="!showAddress"
          type="button"
          class="appView__toolBtn"
          title="اطلاعات برنامه"
          @click="$emit('openPageInfo')"
      >
        <Icon :is="saxIcon.guide" class="appView__toolIcon" size="sm"/>
      </button>
    </div>

    <div v-if="showAddress" class="appView__addressWrap">
      <input
          :value="addressInput"
          type="text"
          class="appView__address"
          dir="ltr"
          spellcheck="false"
          autocomplete="off"
          placeholder="/"
          @input="$emit('update:addressInput', $event.target.value)"
          @focus="$emit('addressFocus')"
          @blur="$emit('addressBlur')"
          @keydown.enter.prevent="$emit('submitAddress')"
      >
      <button
          type="button"
          class="appView__addressInfo"
          title="اطلاعات برنامه"
          @click="$emit('openPageInfo')"
      >
        <Icon :is="saxIcon.guide" class="appView__addressInfoIcon" size="sm"/>
      </button>
    </div>
  </div>
</template>

<script setup>
import {saxIcon} from '@/const/icons.js';
import Icon from '@/views/components/widgets/Icon.vue';

defineProps({
  loading: {
    type: Boolean,
    default: false,
  },
  canGoBack: {
    type: Boolean,
    default: false,
  },
  canGoForward: {
    type: Boolean,
    default: false,
  },
  addressInput: {
    type: String,
    default: '/',
  },
  showNav: {
    type: Boolean,
    default: true,
  },
  showAddress: {
    type: Boolean,
    default: true,
  },
});

defineEmits([
  'update:addressInput',
  'goBack',
  'goForward',
  'reload',
  'submitAddress',
  'openPageInfo',
  'addressFocus',
  'addressBlur',
]);
</script>

<template>
  <Page title="ظاهری" class="pageAppearance">
    <div class="pageAppearance__images">
      <div class="title">انتخاب تصویر زمینه</div>
      <div class="images">
        <div
            v-for="image in backgrounds"
            :key="image"
            class="image-item"
            :class="{ 'selected': image === selectedBackground }"
            @click="changeBackground(image)"
            :style="{ backgroundImage: `url(${image})` }">
        </div>
      </div>
    </div>
  </Page>
</template>

<script setup>
import { ref } from 'vue';

import {useBackground} from "@/views/composables/useBackground.js";

const { backgrounds, selectedBackground, changeBackground } = useBackground();
</script>

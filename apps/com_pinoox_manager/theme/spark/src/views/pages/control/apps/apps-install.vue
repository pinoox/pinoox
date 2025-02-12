<template>
  <Page title="ظاهری" class="pageAppearance">
    <PageSection title="انتخاب تصویر زمینه">
      <div class="gallery flex flex-wrap gap-4">
        <div
            v-for="i in backgrounds"
            :key="i"
            @click="changeBackground(i)"
            class="gallery__image  rounded-xs cursor-pointer overflow-hidden rounded-lg transition-transform duration-300 ease-in-out hover:scale-105"
            :class="{
              'active': selectedBackground === i
            }"
        >
          <img :src="i" class="w-full h-auto">
        </div>
      </div>
    </PageSection>
  </Page>
</template>

<script setup>
import { useBackground } from "@/views/composables/useBackground.js";

const { backgrounds, selectedBackground, changeBackground } = useBackground();

</script>
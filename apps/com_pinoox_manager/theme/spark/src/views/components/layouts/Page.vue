<template>
  <section class="page">
    <div class="page__header">
      <div class="page__header-title">{{ title }}</div>
    </div>
    <div v-if="hasToolbar" class="page__toolbar">
      <slot name="toolbar"></slot>
    </div>
    <div class="page__content" :class="{'hasToolbar':hasToolbar}">
      <slot></slot>
    </div>
  </section>
</template>

<script setup>
import {computed, onMounted, onUnmounted, useSlots} from "vue";
import {useRouter} from "vue-router";

const props = defineProps({
  title: {
    type: String,
  },
});

const router = useRouter();
const slots = useSlots();

const hasToolbar = computed(() => !!slots.toolbar);

const handleKeyPress = (event) => {
  if (event.key === "Escape") {
    if (window.history.length > 1) {
      router.back();
    } else {
      router.push("/");
    }
  }
};

onMounted(() => {
  window.addEventListener("keydown", handleKeyPress);
});

onUnmounted(() => {
  window.removeEventListener("keydown", handleKeyPress);
});
</script>
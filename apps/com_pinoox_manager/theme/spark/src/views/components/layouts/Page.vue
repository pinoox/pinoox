<template>
  <section class="page">
    <div class="page__header">
      <div class="page__header-title">{{ title }}</div>
    </div>
    <div class="page__content">
      <slot></slot>
    </div>
  </section>
</template>

<script setup>
import {onMounted, onUnmounted} from "vue";
import {useRouter} from "vue-router";

const props = defineProps({
  title: {
    type: String,
  },
});

const router = useRouter();

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
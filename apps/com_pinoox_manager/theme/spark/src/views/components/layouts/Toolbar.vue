<template>
  <div class="toolbar fixed top-0 left-0 w-full flex items-center justify-between px-4">
    <div class="toolbar__brand">
      <router-link :to="{name:'desktop'}">PINOOX</router-link>
    </div>

    <div class="toolbar__datetime">
      <div class="toolbar__datetime-time">{{ formattedTime }}</div>
      <div class="toolbar__datetime-date">{{ formattedDate }}</div>
    </div>
  </div>
</template>

<script setup>
import {ref, onMounted} from "vue";

const formattedTime = ref("");
const formattedDate = ref("");

const updateTime = () => {
  const now = new Date();
  formattedTime.value = now.toLocaleTimeString("fa-IR", {hour: "2-digit", minute: "2-digit"});
  formattedDate.value = now.toLocaleDateString("fa-IR");
};

// Update time every second
onMounted(() => {
  updateTime();
  setInterval(updateTime, 1000);
});
</script>

<template>
  <div class="toolbar fixed top-0 left-0 w-full flex items-center px-4">
    <div class="toolbar__brand">
      <button type="button" class="toolbar__action" @click="navigate" aria-label="Pinoox">
        <img src="@/assets/media/pinoox/logo.png" alt="Pinoox" class="toolbar__logo" width="28" height="28">
      </button>
    </div>

    <div class="toolbar__datetime">
      <div class="toolbar__datetime-time">{{ formattedTime }}</div>
      <div class="toolbar__datetime-date">{{ formattedDate }}</div>
    </div>
    <div class="toolbar__account">
      <button type="button" class="toolbar__action" @click="logout" aria-label="خروج">
        <Icon :is="saxIcon.logout" size="sm"/>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/modules/auth.js";
import { saxIcon } from "@/const/icons.js";
import { formatClockTime } from "@/views/composables/useServerClock.js";

const authStore = useAuthStore();
const formattedTime = ref("");
const formattedDate = ref("");
const route = useRoute();
const router = useRouter();

const updateTime = () => {
  const now = new Date();
  formattedTime.value = formatClockTime(now);
  formattedDate.value = now.toLocaleDateString("fa-IR");
};

const navigate = () => {
  if (route.path === "/") {
    router.push("/control/apps");
  } else {
    router.push({ name: "desktop" });
  }
};

const logout = () => {
  authStore.logout().then(() => {
    router.replace({name: 'login'});
  });
};

onMounted(() => {
  updateTime();
  setInterval(updateTime, 1000);
});
</script>
<template>
  <div class="toolbar fixed top-0 left-0 w-full flex items-center px-4">
    <div class="toolbar__brand">
      <a @click="navigate" class="cursor-pointer">PINOOX</a>
    </div>

    <div class="toolbar__datetime">
      <div class="toolbar__datetime-time">{{ formattedTime }}</div>
      <div class="toolbar__datetime-date">{{ formattedDate }}</div>
    </div>
    <div class="toolbar__account">
      <span @click="logout">خروج</span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import {useAuthStore} from "@/stores/modules/auth.js";

const authStore = useAuthStore();
const formattedTime = ref("");
const formattedDate = ref("");
const route = useRoute();
const router = useRouter();

const updateTime = () => {
  const now = new Date();
  formattedTime.value = now.toLocaleTimeString("fa-IR", { hour: "2-digit", minute: "2-digit" });
  formattedDate.value = now.toLocaleDateString("fa-IR");
};

const navigate = () => {
  if (route.path === "/") {
    router.push("/control/appearance");
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
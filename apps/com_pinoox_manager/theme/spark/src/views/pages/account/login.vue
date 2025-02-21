<template>
  <div class="pageLogin">

    <div class="pageLogin__box">
      <div class="pageLogin__box--header">
        <div class="pageLogin__box--header-pinoox">
          <img src="@/assets/media/pinoox/logo.png" alt="pinoox logo">
          <h1>pinoox manager</h1>


        </div>
      </div>

      <div class="pageLogin__box--body" @keyup.enter="handleLogin">
        <div class="form">
          <Input v-model="params.username" type="text" label="نام کاربری"
                 placeholder="نام کاربری را وارد کنید"/>
          <Input v-model="params.password" type="password" label="رمز عبور"
                 placeholder="رمز عبور را وارد کنید"/>

          <Button
              label="ورود"
              variant="light"
              outline
              full-width
              :is-loading="isLoading"
              @click="handleLogin"
          />
        </div>
      </div>
    </div>

    <a href="http://pinoox.com/" target="_blank" class="pageLogin__copyright">pinoox.com</a>

  </div>

</template>

<script setup>
import {ref} from "vue";
import {useRouter} from "vue-router";
import {authAPI} from "@api/auth.js";
import {useAuthStore} from "@/stores/modules/auth.js";

const authStore = useAuthStore();
const router = useRouter()
const isLoading = ref(false);
const params = ref({
  username: null,
  password: null,
})

const handleLogin = () => {
  isLoading.value = true;
  authAPI.login(params.value).then((response) => {
    let login_key = response.data.result;
    authStore.login(login_key);
  }).then(async () => {
    await authStore.canUserAccess();
    await router.push({name: 'desktop'});
  }).finally(() => isLoading.value = false);
};
</script>
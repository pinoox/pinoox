<template>
  <div class="pageLogin">

    <div class="pageLogin__box">
      <div class="pageLogin__box--header">
        <div class="pageLogin__box--header-pinoox">
          <img src="@/assets/media/pinoox/logo.png" alt="pinoox logo">
          <h1>pinoox manager</h1>


        </div>
      </div>

      <div class="pageLogin__box--body">
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
import {reactive, ref} from "vue";
import {useRouter} from "vue-router";

const router = useRouter()
const params = reactive({username: null, password: null});
const isLoading = ref(false);

const handleLogin = () => {
  isLoading.value = true;

  setTimeout(() => {
    isLoading.value = false
    router.push("/");
  }, 1500);
};
</script>
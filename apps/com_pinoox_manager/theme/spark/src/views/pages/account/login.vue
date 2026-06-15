<template>
    <div class="pageLogin">
        <div class="pageLogin__scrim" aria-hidden="true"/>

        <div class="pageLogin__box">
            <div class="pageLogin__box--header">
                <div class="pageLogin__brand">
                    <img src="@/assets/media/pinoox/logo.png" alt="pinoox logo" class="pageLogin__logo">
                    <div class="pageLogin__brand-text">
                        <h1>pinoox manager</h1>
                        <p>برای ادامه وارد حساب کاربری شوید</p>
                    </div>
                </div>
            </div>

            <div class="pageLogin__box--body" @keyup.enter="handleLogin">
                <div class="pageLogin__form">
                    <Input
                        v-model="params.username"
                        type="text"
                        label="نام کاربری"
                        placeholder="نام کاربری را وارد کنید"
                    />
                    <Input
                        v-model="params.password"
                        type="password"
                        label="رمز عبور"
                        placeholder="رمز عبور را وارد کنید"
                    />

                    <Button
                        class="pageLogin__submit"
                        label="ورود به پنل"
                        variant="primary"
                        size="lg"
                        full-width
                        :is-loading="isLoading"
                        @click="handleLogin"
                    />
                </div>
            </div>
        </div>

        <a href="http://pinoox.com/" target="_blank" rel="noopener noreferrer" class="pageLogin__copyright">
            pinoox.com
        </a>
    </div>
</template>

<script setup>
import {ref} from "vue";
import {useRouter} from "vue-router";
import {authAPI} from "@api/auth.js";
import {useAuthStore} from "@/stores/modules/auth.js";
import {unwrapResponse} from "@utils/helpers/apiHelper.js";

const authStore = useAuthStore();
const router = useRouter();
const isLoading = ref(false);
const params = ref({
    username: null,
    password: null,
});

const handleLogin = () => {
    isLoading.value = true;
    authAPI.login(params.value).then((response) => {
        let login_key = unwrapResponse(response);
        authStore.login(login_key);
    }).then(async () => {
        await authStore.canUserAccess(true);
        await router.push({name: 'desktop'});
    }).finally(() => isLoading.value = false);
};
</script>

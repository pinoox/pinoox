<template>
    <Notifications class="notification"/>

    <div v-if="isSingle" :style="{ backgroundImage: `url(${selectedBackground})` }"
         class="w-full h-screen bg-cover bg-center">
        <RouterView/>
    </div>
    <div v-else-if="isShowApp" :style="{ backgroundImage: `url(${selectedBackground})` }"
         class="w-full h-screen bg-cover bg-center">
        <Toolbar v-if="hasToolbar"/>
        <RouterView/>
    </div>

    <ModalTarget/>
</template>

<script setup>
import {useBackground} from "./views/composables/useBackground.js";
import {useRouteMeta} from "@views/composables/useRouteMeta.js";
import {showSuccessAlert, showErrorAlert} from "@utils/helpers/alertHelper.js";
import {httpEvent} from "@global";
import {computed, watch} from "vue";
import {useAuthStore} from "@/stores/modules/auth.js";
import {useAppStore} from "@/stores/modules/app.js";
import {useRouteStore} from "@/stores/modules/route.js";

const {selectedBackground} = useBackground();
const {hasToolbar, isSingle} = useRouteMeta();
const authStore = useAuthStore();
const appStore = useAppStore();
const routeStore = useRouteStore();
const isShowApp = computed(() => {
    return authStore.isAuth;
   // return authStore.isAuth && appStore.isLoaded && routeStore.isLoaded;
});


httpEvent('error_response', showErrorAlert);
httpEvent('response', showSuccessAlert);
watch(() => authStore.isAuth, async () => {
    if (authStore.isAuth) {
        await appStore.getApps();
        await routeStore.getRoutes();
    } else
    {
        appStore.destroyApps();
        routeStore.destroyRoutes();
    }
});
</script>
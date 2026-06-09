<template>

    <Notifications class="notification"/>



    <div v-if="isSingle" :style="bgStyle" class="w-full h-screen bg-cover bg-center">

        <RouterView/>

    </div>

    <div v-else-if="authStore.isAuth && isBooting" class="desktop-loading">

        در حال بارگذاری...

    </div>

    <div v-else-if="authStore.isAuth" :style="bgStyle" class="w-full h-screen bg-cover bg-center">

        <Toolbar v-if="hasToolbar"/>

        <RouterView/>

        <Dockbar v-if="showDock" :apps="dockApps"/>

        <AppViewSessions/>

    </div>



    <Teleport to="body">

        <ModalTarget group="default">

            <ModalOverlay class="vue-modal-overlay"/>

        </ModalTarget>

    </Teleport>

</template>



<script setup>

import {ModalOverlay, ModalTarget} from '@kolirt/vue-modal';

import {useBackground} from "./views/composables/useBackground.js";
import {DEFAULT_FALLBACK_BACKGROUND} from "@utils/helpers/backgroundHelper.js";

import {useRouteMeta} from "@views/composables/useRouteMeta.js";

import {showSuccessAlert, showErrorAlert} from "@utils/helpers/alertHelper.js";

import {httpEvent} from "@global";

import {computed, ref, watch} from "vue";

import {useAuthStore} from "@/stores/modules/auth.js";

import {useAppStore} from "@/stores/modules/app.js";

import {useRouteStore} from "@/stores/modules/route.js";

import {useOptionsStore} from "@/stores/modules/options.js";
import {useDockApps} from "@/views/composables/useDockApps.js";
import Dockbar from "@/views/components/widgets/Dockbar.vue";
import AppViewSessions from "@/views/pages/app-view/AppViewSessions.vue";



const {selectedBackground} = useBackground();

const {hasToolbar, isSingle, showDock} = useRouteMeta();

const authStore = useAuthStore();

const appStore = useAppStore();

const routeStore = useRouteStore();

const optionsStore = useOptionsStore();
const { dockApps } = useDockApps();
const isBooting = ref(false);



const bgStyle = computed(() => {

    if (selectedBackground.value) {

        return { backgroundImage: `url(${selectedBackground.value})` };

    }

    return { background: DEFAULT_FALLBACK_BACKGROUND };

});



httpEvent('error_response', showErrorAlert);

httpEvent('response', showSuccessAlert);



watch(() => authStore.isAuth, async (loggedIn) => {

    if (loggedIn) {

        isBooting.value = true;

        try {

            await optionsStore.load();

            await Promise.all([appStore.getApps(), routeStore.getRoutes()]);

        } finally {

            isBooting.value = false;

        }

    } else {

        optionsStore.reset();

        appStore.destroyApps();

        routeStore.destroyRoutes();

    }

}, {immediate: true});

</script>



<template>

    <SparkNotifications/>

    <ManagerBootLoading
        v-if="authStore.isLoggingOut"
        status="در حال خروج"
    />



    <div v-if="isSingle" :style="bgStyle" class="w-full h-screen bg-cover bg-center">

        <RouterView/>

    </div>

    <template v-else-if="authStore.isAuth">
        <Transition name="manager-boot-fade" mode="out-in">
            <ManagerBootLoading v-if="isBooting" key="boot"/>
            <div v-else key="desktop" :style="bgStyle" class="w-full h-screen bg-cover bg-center">
                <Toolbar v-if="hasToolbar"/>
                <RouterView v-slot="{ Component, route }">
                    <KeepAlive include="DesktopView">
                        <component :is="resolveMainComponent(Component, route)"/>
                    </KeepAlive>
                </RouterView>
                <Dockbar v-if="showDockBar" :apps="dockApps"/>
                <PackageInstallerDropOverlay :visible="isPackageDropActive"/>
                <PackageInstallerFloater/>
                <AppViewAdvanced v-if="isAdvancedAppView"/>
                <ControlPanelAdvanced v-if="isAdvancedAppView"/>
            </div>
        </Transition>
    </template>



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

import {useManagerChrome} from "@/views/composables/useManagerChrome.js";

import {computed, ref, watch} from "vue";

import {useAuthStore} from "@/stores/modules/auth.js";

import {useAppStore} from "@/stores/modules/app.js";

import {useRouteStore} from "@/stores/modules/route.js";

import {useOptionsStore} from "@/stores/modules/options.js";
import {useDockApps} from "@/views/composables/useDockApps.js";
import Dockbar from "@/views/components/widgets/Dockbar.vue";
import PackageInstallerFloater from "@/views/components/widgets/PackageInstallerFloater.vue";
import PackageInstallerDropOverlay from "@/views/components/widgets/PackageInstallerDropOverlay.vue";
import AppViewAdvanced from "@/views/pages/app-view/AppViewAdvanced.vue";
import ControlPanelAdvanced from "@/views/pages/control/ControlPanelAdvanced.vue";
import SparkNotifications from "@/views/components/widgets/SparkNotifications.vue";
import ManagerBootLoading from "@/views/components/layouts/ManagerBootLoading.vue";
import {useAppViewMode} from "@/views/composables/useAppViewMode.js";
import {isControlRoute} from "@/views/composables/useControlPanel.js";
import PageDesktop from "@/views/pages/desktop/desktop-view.vue";
import {pushSystemNotifications} from "@/views/composables/useSystemNotifications.js";
import {useNotificationStore} from "@/stores/modules/notification.js";
import {usePackageInstallerDrop} from "@/views/composables/usePackageInstallerDrop.js";



const {selectedBackground} = useBackground();

const {hasToolbar, isSingle, showDockBar} = useManagerChrome();

const authStore = useAuthStore();

const appStore = useAppStore();

const routeStore = useRouteStore();

const optionsStore = useOptionsStore();
const { dockApps } = useDockApps();
const {isAdvanced: isAdvancedAppView} = useAppViewMode();
const isBooting = ref(false);
const canAcceptPackageDrop = computed(() => authStore.isAuth && !isSingle.value && !isBooting.value);
const {isDragging: isPackageDropActive} = usePackageInstallerDrop(canAcceptPackageDrop);

function resolveMainComponent(Component, route) {
    if (isAdvancedAppView.value && isControlRoute(route)) {
        return PageDesktop;
    }

    return Component;
}



const bgStyle = computed(() => {

    if (selectedBackground.value) {

        return { backgroundImage: `url(${selectedBackground.value})` };

    }

    return { background: DEFAULT_FALLBACK_BACKGROUND };

});



watch(() => authStore.isAuth, async (loggedIn) => {

    if (loggedIn) {

        isBooting.value = true;

        try {

            await optionsStore.load();

            await Promise.all([
                appStore.getApps(),
                routeStore.getRoutes(),
            ]);

        } finally {

            isBooting.value = false;

        }

        await pushSystemNotifications();

    } else {

        optionsStore.reset();

        appStore.destroyApps();

        routeStore.destroyRoutes();

        useNotificationStore().reset();

    }

}, {immediate: true});

</script>



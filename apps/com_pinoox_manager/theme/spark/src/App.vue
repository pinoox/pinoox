<template>

  <Notifications class="notification"/>

  <div :style="{ backgroundImage: `url(${selectedBackground})` }"
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

const {selectedBackground} = useBackground();
const {hasToolbar} = useRouteMeta();


httpEvent('error_response', showErrorAlert);
httpEvent('response', showSuccessAlert);
</script>
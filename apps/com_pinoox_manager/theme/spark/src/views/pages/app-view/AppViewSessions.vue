<template>
  <Teleport to="body">
    <AppViewPanel
        v-for="entry in aliveSessions"
        v-show="isSessionVisible(entry.session.mode)"
        :key="entry.packageName"
        :package_name="entry.packageName"
        :overlay="entry.session.mode === 'floating'"
        :fullscreen="entry.session.mode === 'fullscreen'"
        :z-index="entry.session.zIndex"
    />
  </Teleport>
</template>

<script setup>
import {computed, watch} from 'vue';
import {useRoute} from 'vue-router';
import {useAppViewWindowStore} from '@/stores/modules/appViewWindow.js';
import AppViewPanel from '@/views/pages/app-view/AppViewPanel.vue';

const route = useRoute();
const appViewWindow = useAppViewWindowStore();

const aliveSessions = computed(() =>
    Object.entries(appViewWindow.sessions)
        .filter(([, session]) => session.mode !== 'hidden')
        .map(([packageName, session]) => ({packageName, session}))
        .sort((a, b) => a.session.zIndex - b.session.zIndex)
);

function isSessionVisible(mode) {
  return mode === 'floating' || mode === 'fullscreen';
}

watch(() => route.name, (name) => {
    appViewWindow.demoteFullscreenIfNeeded(name);
}, {immediate: true});
</script>

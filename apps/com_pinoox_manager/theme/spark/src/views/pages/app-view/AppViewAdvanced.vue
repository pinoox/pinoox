<template>
  <Teleport to="body">
    <AppViewPanelAdvanced
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
import {useRoute, useRouter} from 'vue-router';
import {useAppViewWindowStore} from '@/stores/modules/appViewWindow.js';
import {useAppViewMode} from '@/views/composables/useAppViewMode.js';
import AppViewPanelAdvanced from '@/views/pages/app-view/AppViewPanelAdvanced.vue';

const route = useRoute();
const router = useRouter();
const appViewWindow = useAppViewWindowStore();
const {isSimple} = useAppViewMode();

const aliveSessions = computed(() => {
  const ordered = [...appViewWindow.panelOrder];

  for (const packageName of Object.keys(appViewWindow.sessions)) {
    if (!ordered.includes(packageName)) {
      ordered.push(packageName);
    }
  }

  return ordered
      .map((packageName) => {
        const session = appViewWindow.sessions[packageName];

        if (!session || session.mode === 'hidden') {
          return null;
        }

        return {packageName, session};
      })
      .filter(Boolean);
});

function isSessionVisible(mode) {
  return mode === 'floating' || mode === 'fullscreen';
}

function syncRouteSession() {
  if (isSimple.value || route.name !== 'app-view') {
    return;
  }

  const packageName = String(route.params.package_name ?? '');

  if (packageName === '') {
    return;
  }

  const session = appViewWindow.sessions[packageName];

  if (session?.mode === 'minimized') {
    const restoreMode = appViewWindow.restoreSession(packageName);

    if (restoreMode === 'floating') {
      router.replace({name: 'desktop'});
    }

    return;
  }

  if (session?.mode === 'floating') {
    appViewWindow.focus(packageName);

    if (route.name === 'app-view') {
      router.replace({name: 'desktop'});
    }

    return;
  }

  appViewWindow.openFullscreen(packageName);
}

watch(() => route.name, (name) => {
  appViewWindow.demoteFullscreenIfNeeded(name);
}, {immediate: true});

watch(
    () => [route.name, route.params.package_name, isSimple.value],
    () => {
      syncRouteSession();
    },
    {immediate: true},
);

watch(isSimple, (simple) => {
  if (simple) {
    appViewWindow.dismissAll();
  }
});
</script>

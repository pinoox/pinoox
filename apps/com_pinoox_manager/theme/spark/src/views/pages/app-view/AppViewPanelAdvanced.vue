<template>
  <ManagerWindow
      ref="windowRef"
      :overlay="overlay"
      :fullscreen="fullscreen"
      :z-index="zIndex"
      :shell-compact="isCompactFloating"
      :session-rect="sessionRect"
      :on-rect-commit="commitRect"
      :on-focus="focusWindow"
      :on-interact="setFrameInteracting"
      @close="closeWindow"
      @minimize="minimizeWindow"
      @toggle-float="toggleFloating"
  >
    <template #title>
      <template v-if="app">
        <AppIcon v-if="app.icon_lucide || app.icon" v-bind="appIconProps(app)" size="sm"/>
        <span>{{ app.name }}</span>
      </template>
    </template>

    <template #toolbar-after>
      <AppViewBrowseBar
          v-model:address-input="addressInput"
          :loading="loading"
          :can-go-back="canGoBack"
          :can-go-forward="canGoForward"
          :show-nav="true"
          :show-address="showAddressBar && !isCompactFloating"
          @go-back="frameGoBack"
          @go-forward="frameGoForward"
          @reload="reload"
          @submit-address="submitAddress"
          @open-page-info="openPageInfo"
          @address-focus="onAddressFocus"
          @address-blur="onAddressBlur"
      />
    </template>

    <template #before-body>
      <div
          class="appView__progress"
          :class="{ 'is-active': loading, 'is-complete': progress >= 100 }"
          aria-hidden="true"
      >
        <div class="appView__progressTrack">
          <div class="appView__progressBar" :style="{ width: `${progress}%` }"/>
        </div>
      </div>
    </template>

    <div class="appView__frame">
      <iframe
          ref="frameRef"
          class="appView__iframe"
          :title="app?.name || 'پیش‌نمایش اپ'"
          @load="handleFrameLoad"
      ></iframe>
    </div>

    <template #footer>
      <footer
          v-if="isCompactFloating && overlay && showAddressBar"
          class="appView__dock"
      >
        <AppViewBrowseBar
            v-model:address-input="addressInput"
            :loading="loading"
            :can-go-back="canGoBack"
            :can-go-forward="canGoForward"
            :show-nav="false"
            :show-address="showAddressBar"
            @submit-address="submitAddress"
            @open-page-info="openPageInfo"
            @address-focus="onAddressFocus"
            @address-blur="onAddressBlur"
        />
      </footer>
    </template>
  </ManagerWindow>
</template>

<script setup>
import {computed, onMounted, onUnmounted, ref, watch} from 'vue';
import {useRouter} from 'vue-router';
import {openModal} from '@kolirt/vue-modal';
import {useAppStore} from '@/stores/modules/app.js';
import {appIconProps} from '@utils/helpers/appIconProps.js';
import {useAppViewWindowStore} from '@/stores/modules/appViewWindow.js';
import {buildSecretViewEmbedUrl} from '@/views/composables/useSecretView.js';
import {isAppViewCloseMessage} from '@/views/composables/useAppViewBridge.js';
import {useAppViewFrameLoading} from '@/views/composables/useAppViewFrameLoading.js';
import {
  buildAppViewNavigateUrl,
  normalizeRouteInput,
  parseAppRouteParts,
  resolveAppRouteFromHref,
} from '@/views/composables/appViewRoute.js';
import ManagerWindow from '@/views/components/layouts/ManagerWindow.vue';
import AppViewBrowseBar from '@/views/pages/app-view/AppViewBrowseBar.vue';
import ModalAppViewInfo from '@/views/pages/app-view/modal-app-view-info.vue';

const COMPACT_WIDTH = 640;

const props = defineProps({
  package_name: {
    type: String,
    required: true,
  },
  overlay: {
    type: Boolean,
    default: false,
  },
  fullscreen: {
    type: Boolean,
    default: false,
  },
  zIndex: {
    type: Number,
    default: 10050,
  },
});

const router = useRouter();
const appStore = useAppStore();
const appViewWindow = useAppViewWindowStore();

const windowRef = ref(null);
const frameRef = ref(null);
const addressInput = ref('/');
const addressFocused = ref(false);
const pendingAddressRoute = ref(null);
const shellWidth = ref(0);
const embedUrl = buildSecretViewEmbedUrl(props.package_name);
let embedAssigned = false;
let shellObserver = null;

const isCompactFloating = computed(() =>
    props.overlay && shellWidth.value > 0 && shellWidth.value < COMPACT_WIDTH
);

const isFrameVisible = computed(() => {
  const session = appViewWindow.sessions[props.package_name];

  if (!session) {
    return false;
  }

  return session.mode === 'floating' || session.mode === 'fullscreen';
});

const sessionRect = computed(() => appViewWindow.sessions[props.package_name]?.rect ?? null);

const {
  loading,
  progress,
  frameHref,
  navigationMethod,
  canGoBack,
  canGoForward,
  startLoading,
  onFrameLoad,
  startHrefPolling,
  frameGoBack,
  frameGoForward,
  destroy,
  setFrameInteracting,
} = useAppViewFrameLoading(props.package_name, isFrameVisible);

const app = computed(() =>
    appStore.appList?.find((item) => item.package_name === props.package_name) ?? null
);

const showAddressBar = computed(() => app.value?.app_view?.address_bar !== false);

const displayRoute = computed(() =>
    resolveAppRouteFromHref(frameHref.value, props.package_name) ?? '/'
);

const routeParts = computed(() => parseAppRouteParts(displayRoute.value));

const pageInfo = computed(() => ({
  route: displayRoute.value,
  method: navigationMethod.value,
  path: routeParts.value.path,
  query: routeParts.value.query,
  hash: routeParts.value.hash,
  packageName: props.package_name,
  appName: app.value?.name ?? '—',
  developer: app.value?.developer ?? '',
  version: app.value?.version ?? '',
}));

function commitRect(rect) {
  appViewWindow.updateRect(props.package_name, rect);
}

function focusWindow() {
  appViewWindow.focus(props.package_name);
}

function syncAddressFromFrame() {
  if (addressFocused.value) {
    return;
  }

  const frameRoute = resolveAppRouteFromHref(frameHref.value, props.package_name);

  if (pendingAddressRoute.value) {
    if (frameRoute !== null) {
      const normalizedFrame = normalizeRouteInput(frameRoute);
      const normalizedPending = pendingAddressRoute.value;

      if (normalizedFrame === normalizedPending) {
        pendingAddressRoute.value = null;
        addressInput.value = frameRoute;
        return;
      }

      if (!loading.value) {
        pendingAddressRoute.value = null;
        addressInput.value = frameRoute;
        return;
      }
    }

    addressInput.value = pendingAddressRoute.value;
    return;
  }

  if (frameRoute !== null) {
    addressInput.value = frameRoute;
  }
}

watch(frameHref, syncAddressFromFrame);
watch(loading, syncAddressFromFrame);

function navigateToRoute(routeValue) {
  const route = normalizeRouteInput(routeValue);
  const url = buildAppViewNavigateUrl(props.package_name, route);

  pendingAddressRoute.value = route;
  addressInput.value = route;
  startLoading();

  if (!frameRef.value) {
    return;
  }

  const frame = frameRef.value;

  try {
    frame.contentWindow.location.href = url;
  } catch {
    frame.src = url;
  }
}

function submitAddress() {
  navigateToRoute(addressInput.value);
  addressFocused.value = false;
}

function onAddressFocus() {
  addressFocused.value = true;
}

function onAddressBlur() {
  addressFocused.value = false;
  syncAddressFromFrame();
}

function reload() {
  const route = normalizeRouteInput(
      resolveAppRouteFromHref(frameHref.value, props.package_name)
      ?? addressInput.value
      ?? '/',
  );

  pendingAddressRoute.value = route;
  addressInput.value = route;
  startLoading();

  if (!frameRef.value) {
    return;
  }

  const frame = frameRef.value;

  try {
    frame.contentWindow.location.reload();
  } catch {
    frame.src = buildAppViewNavigateUrl(props.package_name, route);
  }
}

function handleFrameLoad() {
  onFrameLoad(frameRef.value);
  syncAddressFromFrame();
}

function openPageInfo() {
  void openModal(ModalAppViewInfo, {
    props: {
      info: pageInfo.value,
    },
  }).catch(() => {});
}

function closeWindow() {
  const wasFullscreen = props.fullscreen;

  appViewWindow.closeSession(props.package_name);

  if (wasFullscreen) {
    router.push({name: 'desktop'});
  }
}

function minimizeWindow() {
  appViewWindow.minimize({
    package_name: props.package_name,
    appName: app.value?.name ?? props.package_name,
    icon: app.value?.icon ?? '',
    restoreMode: props.overlay ? 'floating' : 'fullscreen',
  });
  router.push({name: 'desktop'});
}

function toggleFloating() {
  if (props.overlay) {
    appViewWindow.openFullscreen(props.package_name);
    router.push({name: 'app-view', params: {package_name: props.package_name}});
    return;
  }

  appViewWindow.enterFloating(props.package_name);
  router.push({name: 'desktop'});
}

function closePreview() {
  const wasFullscreen = props.fullscreen;

  appViewWindow.closeSession(props.package_name);

  if (wasFullscreen) {
    router.push({name: 'desktop'});
  }
}

function onFrameMessage(event) {
  if (event.origin !== window.location.origin) {
    return;
  }

  if (!isAppViewCloseMessage(event.data)) {
    return;
  }

  closePreview();
}

function updateShellWidth() {
  shellWidth.value = windowRef.value?.shellRef?.offsetWidth ?? 0;
}

function attachFrame(frame) {
  if (!frame) {
    return;
  }

  if (!embedAssigned) {
    frame.src = embedUrl;
    embedAssigned = true;

    if (isFrameVisible.value) {
      startLoading();
    }
  }

  startHrefPolling(frame);
}

watch(frameRef, attachFrame, {immediate: true});

watch(
    () => windowRef.value?.shellRef,
    (shell) => {
      shellObserver?.disconnect();
      shellObserver = null;

      if (shell && typeof ResizeObserver !== 'undefined') {
        shellObserver = new ResizeObserver(updateShellWidth);
        shellObserver.observe(shell);
      }

      updateShellWidth();
    },
    {flush: 'post'},
);

onMounted(async () => {
  window.addEventListener('message', onFrameMessage);
  appViewWindow.ensureSession(props.package_name);

  if (!appStore.appList?.length) {
    await appStore.getApps();
  }

  updateShellWidth();
});

onUnmounted(() => {
  window.removeEventListener('message', onFrameMessage);
  shellObserver?.disconnect();
  shellObserver = null;
  destroy();
});
</script>

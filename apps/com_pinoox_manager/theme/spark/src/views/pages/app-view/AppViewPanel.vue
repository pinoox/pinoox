<template>
  <section
      class="appView"
      :class="{
        'is-floating': isFloating,
        'is-overlay': isFloating,
        'is-fullscreenPanel': isFullscreen,
      }"
      :style="panelStyle"
      @mousedown="onPanelFocus"
  >
    <div
        ref="shellRef"
        class="appView__shell"
        :class="{
          'is-floatingShell': isFloating,
          'is-interacting': interacting,
          'is-dragging': isDragging,
          'is-resizing': isResizing,
        }"
        :style="isFloating && !interacting ? floatingStyle : undefined"
        @mousedown="onPanelFocus"
    >
      <header
          class="appView__toolbar"
          :class="{ 'is-draggable': isFloating }"
          @mousedown="onToolbarMouseDown"
      >
        <AppViewWindowChrome
            :floating="isFloating"
            @close="closeWindow"
            @minimize="minimizeWindow"
            @toggle-float="toggleFloating"
        />

        <div v-if="app" class="appView__title">
          <img v-if="app.icon" :src="app.icon" :alt="app.name" class="appView__icon">
          <span>{{ app.name }}</span>
        </div>

        <div class="appView__browse" dir="ltr">
          <div class="appView__nav">
            <button
                type="button"
                class="appView__toolBtn"
                :disabled="!canGoBack || loading"
                title="صفحه قبل"
                @click="frameGoBack"
            >
              <Icon :is="saxIcon.arrowLeft" class="appView__toolIcon"/>
            </button>

            <button
                type="button"
                class="appView__toolBtn"
                :disabled="!canGoForward || loading"
                title="صفحه بعد"
                @click="frameGoForward"
            >
              <Icon :is="saxIcon.arrowRight" class="appView__toolIcon"/>
            </button>

            <button
                type="button"
                class="appView__toolBtn"
                :disabled="loading"
                title="بازآوری صفحه"
                @click="reload"
            >
              <Icon :is="saxIcon.refresh" class="appView__toolIcon" :class="{ 'is-spinning': loading }"/>
            </button>
          </div>

          <div class="appView__addressWrap">
            <input
                ref="addressRef"
                v-model="addressInput"
                type="text"
                class="appView__address"
                dir="ltr"
                spellcheck="false"
                autocomplete="off"
                placeholder="/"
                @focus="addressFocused = true"
                @blur="onAddressBlur"
                @keydown.enter.prevent="submitAddress"
            >
            <button
                type="button"
                class="appView__addressInfo"
                title="اطلاعات صفحه"
                @click="openPageInfo"
            >
              <Icon :is="saxIcon.guide" class="appView__addressInfoIcon"/>
            </button>
          </div>
        </div>
      </header>

      <div class="appView__frame">
        <div
            class="appView__progress"
            :class="{ 'is-active': loading, 'is-complete': progress >= 100 }"
            aria-hidden="true"
        >
          <div class="appView__progressTrack">
            <div class="appView__progressBar" :style="{ width: `${progress}%` }"/>
          </div>
        </div>

        <iframe
            :key="frameKey"
            ref="frameRef"
            class="appView__iframe"
            :src="embedUrl"
            :title="app?.name || 'پیش‌نمایش اپ'"
            @load="handleFrameLoad"
        ></iframe>
      </div>

      <div
          v-if="isFloating"
          class="appView__resizeHandle"
          title="تغییر اندازه"
          @mousedown="onResizeStart"
      />
    </div>
  </section>
</template>

<script setup>
import {computed, onMounted, onUnmounted, ref, watch} from 'vue';
import {useRouter} from 'vue-router';
import {openModal} from '@kolirt/vue-modal';
import {saxIcon} from '@/const/icons.js';
import Icon from '@/views/components/widgets/Icon.vue';
import {useAppStore} from '@/stores/modules/app.js';
import {useAppViewWindowStore} from '@/stores/modules/appViewWindow.js';
import {buildSecretViewEmbedUrl} from '@/views/composables/useSecretView.js';
import {isAppViewCloseMessage} from '@/views/composables/useAppViewBridge.js';
import {useAppViewFrameLoading} from '@/views/composables/useAppViewFrameLoading.js';
import {useAppViewFloating} from '@/views/composables/useAppViewFloating.js';
import {
  buildAppViewNavigateUrl,
  normalizeRouteInput,
  parseAppRouteParts,
  resolveAppRouteFromHref,
} from '@/views/composables/appViewRoute.js';
import AppViewWindowChrome from '@/views/pages/app-view/AppViewWindowChrome.vue';
import ModalAppViewInfo from '@/views/pages/app-view/modal-app-view-info.vue';

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

const frameKey = ref(0);
const frameRef = ref(null);
const shellRef = ref(null);
const addressRef = ref(null);
const addressInput = ref('/');
const addressFocused = ref(false);
const pendingAddressRoute = ref(null);

const isFloating = computed(() => props.overlay);
const isFullscreen = computed(() => props.fullscreen);

const sessionRect = computed(() => appViewWindow.sessions[props.package_name]?.rect ?? null);

const panelStyle = computed(() => {
  if (!isFloating.value && !isFullscreen.value) {
    return {};
  }

  return {zIndex: props.zIndex};
});

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
} = useAppViewFrameLoading(props.package_name);

const {
  shellStyle: floatingStyle,
  onDragStart,
  onResizeStart,
  interacting,
  isDragging,
  isResizing,
} = useAppViewFloating(shellRef, sessionRect, {
  onRectCommit: (rect) => appViewWindow.updateRect(props.package_name, rect),
  onInteract: setFrameInteracting,
});

const app = computed(() =>
    appStore.appList?.find((item) => item.package_name === props.package_name) ?? null
);

const embedUrl = computed(() => buildSecretViewEmbedUrl(props.package_name));

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
}));

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

function onPanelFocus() {
  if (isFloating.value) {
    appViewWindow.focus(props.package_name);
  }
}

function onToolbarMouseDown(event) {
  if (!isFloating.value) {
    return;
  }

  onDragStart(event);
}

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
  addressRef.value?.blur();
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
  const wasFullscreen = isFullscreen.value;

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
  const wasFullscreen = isFullscreen.value;

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

watch(frameRef, (frame) => {
  if (frame) {
    startHrefPolling(frame);
  }
}, {immediate: true});

onMounted(async () => {
  window.addEventListener('message', onFrameMessage);
  appViewWindow.ensureSession(props.package_name);

  if (!appStore.appList?.length) {
    await appStore.getApps();
  }
});

onUnmounted(() => {
  window.removeEventListener('message', onFrameMessage);
  destroy();
});
</script>

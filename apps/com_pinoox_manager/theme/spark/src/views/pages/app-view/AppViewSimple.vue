<template>
  <section class="appView appView--simple">
    <div class="appView__toolbar">
      <button type="button" class="appView__back" @click="goBack">
        ← بازگشت
      </button>

      <div v-if="app" class="appView__title">
        <img v-if="app.icon" :src="app.icon" :alt="app.name" class="appView__icon">
        <span>{{ app.name }}</span>
      </div>

      <button
          type="button"
          class="appView__reload"
          :disabled="loading"
          title="بازآوری صفحه"
          @click="reload"
      >
        <Icon :is="saxIcon.refresh" class="appView__reload-icon" :class="{ 'is-spinning': loading }"/>
        <span>بازآوری</span>
      </button>
    </div>

    <div
        class="appView__progress"
        :class="{ 'is-active': loading, 'is-complete': progress >= 100 }"
        aria-hidden="true"
    >
      <div class="appView__progressTrack">
        <div class="appView__progressBar" :style="{ width: `${progress}%` }"/>
      </div>
    </div>

    <div class="appView__frame">
      <iframe
          :key="frameKey"
          ref="frameRef"
          class="appView__iframe"
          :src="embedUrl"
          :title="app?.name || 'پیش‌نمایش اپ'"
          @load="handleFrameLoad"
      ></iframe>
    </div>
  </section>
</template>

<script setup>
import {computed, onMounted, onUnmounted, ref, watch} from 'vue';
import {useRouter} from 'vue-router';
import {saxIcon} from '@/const/icons.js';
import Icon from '@/views/components/widgets/Icon.vue';
import {useAppStore} from '@/stores/modules/app.js';
import {buildSecretViewEmbedUrl} from '@/views/composables/useSecretView.js';
import {isAppViewCloseMessage} from '@/views/composables/useAppViewBridge.js';
import {useAppViewFrameLoading} from '@/views/composables/useAppViewFrameLoading.js';

const props = defineProps({
  package_name: {
    type: String,
    required: true,
  },
});

const router = useRouter();
const appStore = useAppStore();

const isActive = ref(true);
const frameKey = ref(0);
const frameRef = ref(null);

const {
  loading,
  progress,
  onFrameLoad,
  startLoading,
  startHrefPolling,
  destroy,
} = useAppViewFrameLoading(props.package_name, isActive);

const app = computed(() =>
    appStore.appList?.find((item) => item.package_name === props.package_name) ?? null
);

const embedUrl = computed(() => buildSecretViewEmbedUrl(props.package_name));

function goBack() {
  if (window.history.length > 1)
    router.back();
  else
    router.push({name: 'desktop'});
}

function reload() {
  startLoading();
  frameKey.value += 1;
}

function handleFrameLoad() {
  onFrameLoad(frameRef.value);
}

function closePreview() {
  router.push({name: 'desktop'});
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

  if (!appStore.appList?.length) {
    await appStore.getApps();
  }
});

onUnmounted(() => {
  window.removeEventListener('message', onFrameMessage);
  destroy();
});
</script>

<template>
  <section class="appView">
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

    <div class="appView__frame">
      <iframe
          :key="frameKey"
          ref="frameRef"
          class="appView__iframe"
          :src="embedUrl"
          :title="app?.name || 'پیش‌نمایش اپ'"
          @load="loading = false"
      ></iframe>

      <div v-if="loading" class="appView__loading">در حال بارگذاری...</div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { saxIcon } from '@/const/icons.js';
import Icon from '@/views/components/widgets/Icon.vue';
import { useAppStore } from '@/stores/modules/app.js';
import { buildSecretViewEmbedUrl } from '@/views/composables/useSecretView.js';

const props = defineProps({
  package_name: {
    type: String,
    required: true,
  },
});

const router = useRouter();
const appStore = useAppStore();

const loading = ref(true);
const frameKey = ref(0);
const frameRef = ref(null);

const app = computed(() =>
    appStore.appList?.find((item) => item.package_name === props.package_name) ?? null
);

const embedUrl = computed(() => buildSecretViewEmbedUrl(props.package_name));

function goBack() {
  if (window.history.length > 1)
    router.back();
  else
    router.push({ name: 'desktop' });
}

function reload() {
  loading.value = true;
  frameKey.value += 1;
}

onMounted(async () => {
  if (!appStore.appList?.length)
    await appStore.getApps();
});
</script>

<template>
  <Page :title="widget?.title ?? 'ویجت'" class="pageWidgets">
    <template #toolbar>
      <RouterLink :to="{ name: 'widgets' }" class="widgetBack">
        ← بازگشت به لیست ویجت‌ها
      </RouterLink>
    </template>

    <div v-if="loading && !widget" class="widgetGrid__loading">در حال بارگذاری...</div>

    <template v-else-if="widget">
      <PageSection title="پیش‌نمایش">
        <div class="widgetDetailPreview">
          <component :is="previewComponent"/>
        </div>
      </PageSection>

      <PageSection title="نمایش روی دسکتاپ">
        <div class="widgetToggleRow">
          <div>
            <strong>فعال‌سازی ویجت</strong>
            <p>با غیرفعال کردن، این ویجت روی دسکتاپ نمایش داده نمی‌شود.</p>
          </div>
          <label class="switch">
            <input
                type="checkbox"
                :checked="widget.visible"
                @change="onToggleVisibility($event.target.checked)"
            />
          </label>
        </div>
      </PageSection>

      <PageSection v-if="widget.configurable" title="تنظیمات">
        <StorageWidgetSettings v-if="widget.id === 'storage'"/>
      </PageSection>

      <PageSection v-else title="توضیحات">
        <p class="widgetDetailNote">{{ widget.description }}</p>
      </PageSection>
    </template>
  </Page>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Page from '@/views/components/layouts/Page.vue';
import PageSection from '@/views/components/layouts/PageSection.vue';
import StorageWidgetSettings from './StorageWidgetSettings.vue';
import { getWidgetPreview } from './widgetPreviews.js';
import { useWidgetSettings } from './useWidgetSettings.js';

const props = defineProps({
  id: {
    type: String,
    required: true,
  },
});

const route = useRoute();
const router = useRouter();

const { getWidget, loadWidgets, toggleVisibility, loading, widgets } = useWidgetSettings();

const widget = computed(() => getWidget(props.id));
const previewComponent = computed(() => getWidgetPreview(props.id));

async function onToggleVisibility(visible) {
  await toggleVisibility(props.id, visible);
}

onMounted(() => {
  loadWidgets().then(() => {
    if (!getWidget(props.id))
      router.replace({ name: 'widgets' });
  });
});

watch(
  () => widgets.value,
  () => {
    if (!loading.value && !getWidget(props.id))
      router.replace({ name: 'widgets' });
  },
);

watch(
  () => route.params.id,
  async (id) => {
    if (!getWidget(id))
      await loadWidgets();
  },
);
</script>

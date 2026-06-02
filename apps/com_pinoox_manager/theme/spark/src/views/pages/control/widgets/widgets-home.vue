<template>
  <Page title="ویجت‌ها" class="pageWidgets">
    <div class="widgetGrid">
      <RouterLink
          v-for="widget in widgetList"
          :key="widget.id"
          :to="{ name: 'widget-detail', params: { id: widget.id } }"
          class="widgetGrid__card"
      >
        <div class="widgetGrid__preview">
          <component :is="getWidgetPreview(widget.id)" v-if="getWidgetPreview(widget.id)"/>
        </div>

        <div class="widgetGrid__meta">
          <div class="widgetGrid__meta-top">
            <strong>{{ widget.title }}</strong>
            <span
                class="widgetGrid__status"
                :class="{ 'is-active': widget.visible }"
            >
              {{ widget.visible ? 'فعال' : 'غیرفعال' }}
            </span>
          </div>
          <p>{{ widget.description }}</p>
        </div>
      </RouterLink>
    </div>
  </Page>
</template>

<script setup>
import { onMounted } from 'vue';
import Page from '@/views/components/layouts/Page.vue';
import { getWidgetPreview } from './widgetPreviews.js';
import { useWidgetSettings } from './useWidgetSettings.js';

const { widgetList, loadWidgets } = useWidgetSettings();

onMounted(() => {
  loadWidgets();
});
</script>

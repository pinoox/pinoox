<template>
  <WidgetPreviewShell :loading="loading">
    <template #header>
      <span class="widgetPreviewStorage__title">فضای ذخیره‌سازی</span>
    </template>

    <div class="widgetPreviewStorage__body">
      <div class="widgetPreviewStorage__ring">
        <svg viewBox="0 0 42 42" class="widgetPreviewStorage__ring-svg" aria-hidden="true">
          <circle cx="21" cy="21" r="15.9155" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="3.2"/>
          <circle
              cx="21"
              cy="21"
              r="15.9155"
              fill="none"
              :stroke="ringColor"
              stroke-width="3.2"
              stroke-linecap="round"
              :stroke-dasharray="`${displayPercent} 100`"
              transform="rotate(-90 21 21)"
          />
        </svg>
        <span class="widgetPreviewStorage__ring-value">{{ displayPercent }}%</span>
      </div>

      <div class="widgetPreviewStorage__details">
        <ul class="widgetPreviewStorage__stats">
          <li class="widgetPreviewStorage__stat">
            <span class="widgetPreviewStorage__label">فضای مصرف‌شده</span>
            <span class="widgetPreviewStorage__value" dir="ltr">
              <span>GB</span>
              <span>{{ formatGb(storage.use) }}</span>
            </span>
          </li>
          <li class="widgetPreviewStorage__stat">
            <span class="widgetPreviewStorage__label">فضای کل</span>
            <span class="widgetPreviewStorage__value is-muted" dir="ltr">
              <span>GB</span>
              <span>{{ formatGb(storage.total) }}</span>
            </span>
          </li>
        </ul>
      </div>
    </div>
  </WidgetPreviewShell>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { widgetAPI } from '@api/widget.js';
import { unwrapResponse } from '@utils/helpers/apiHelper.js';
import WidgetPreviewShell from './WidgetPreviewShell.vue';

const loading = ref(true);

const storage = ref({
  use: 0,
  total: 0,
  percent: 0,
  size_pending: false,
});

const displayPercent = computed(() => {
  const value = Number(storage.value.percent ?? 0);

  return Math.min(100, Math.max(0, Math.round(value)));
});

const ringColor = computed(() => {
  if (displayPercent.value >= 90)
    return '#f56565';

  if (displayPercent.value >= 75)
    return '#f5a623';

  return '#6ecb8f';
});

function formatGb(value) {
  if (storage.value.size_pending)
    return '—';

  return Number(value ?? 0).toFixed(2);
}

onMounted(async () => {
  try {
    const response = await widgetAPI.storage();
    const data = unwrapResponse(response) ?? {};

    storage.value = {
      ...storage.value,
      ...data,
    };
  } finally {
    loading.value = false;
  }
});
</script>

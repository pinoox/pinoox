<template>
  <DraggableWidget class="storageWidget" initialX="8%" initialY="12%">
    <template #header>
      <div class="storageWidget__head">
        <span class="storageWidget__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <ellipse cx="12" cy="6" rx="8" ry="3" stroke="currentColor" stroke-width="1.5"/>
            <path d="M4 6v5c0 1.66 3.58 3 8 3s8-1.34 8-3V6" stroke="currentColor" stroke-width="1.5"/>
            <path d="M4 11v5c0 1.66 3.58 3 8 3s8-1.34 8-3v-5" stroke="currentColor" stroke-width="1.5"/>
          </svg>
        </span>
        <span class="storageWidget__title">فضای ذخیره‌سازی</span>
      </div>
    </template>

    <div v-if="storage.mode === 'directory' && !storage.path_valid" class="storageWidget__warning">
      مسیر انتخاب‌شده معتبر نیست. از کنترل پنل › ویجت‌ها تنظیمات را بررسی کنید.
    </div>

    <div v-if="storage.mode === 'database' && !storage.path_valid" class="storageWidget__warning">
      جدول فایل‌های دیتابیس در دسترس نیست. تنظیمات ویجت را بررسی کنید.
    </div>

    <WidgetLoading v-if="loading"/>

    <template v-else>
      <div class="storageWidget__body">
        <div
            class="storageWidget__ring"
            :class="barToneClass"
            role="progressbar"
            :aria-valuenow="displayPercent"
            aria-valuemin="0"
            aria-valuemax="100"
            :aria-label="`${displayPercent}% فضای مصرف‌شده`"
        >
          <svg viewBox="0 0 42 42" class="storageWidget__ring-svg" aria-hidden="true">
            <circle class="storageWidget__ring-track" cx="21" cy="21" r="15.9155" fill="none" stroke-width="3.2"/>
            <circle
                class="storageWidget__ring-progress"
                cx="21"
                cy="21"
                r="15.9155"
                fill="none"
                stroke-width="3.2"
                stroke-linecap="round"
                :stroke-dasharray="`${ringProgress} 100`"
                transform="rotate(-90 21 21)"
            />
          </svg>
          <span class="storageWidget__ring-value">{{ displayPercent }}%</span>
        </div>

        <div class="storageWidget__details" :class="barToneClass">
          <ul class="storageWidget__stats">
            <li class="storageWidget__stat">
              <span class="storageWidget__stat-label">فضای مصرف‌شده</span>
              <span class="storageWidget__stat-value is-used" dir="ltr">
                <span class="storageWidget__unit">GB</span>
                <span class="storageWidget__num">{{ formatGb(storage.use) }}</span>
              </span>
            </li>
            <li class="storageWidget__stat">
              <span class="storageWidget__stat-label">فضای کل</span>
              <span class="storageWidget__stat-value is-total" dir="ltr">
                <span class="storageWidget__unit">GB</span>
                <span class="storageWidget__num">{{ formatGb(storage.total) }}</span>
              </span>
            </li>
          </ul>
        </div>
      </div>
    </template>
  </DraggableWidget>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import DraggableWidget from '../widgets/DraggableWidget.vue';
import WidgetLoading from './WidgetLoading.vue';
import { widgetAPI } from '@api/widget.js';
import { unwrapResponse } from '@utils/helpers/apiHelper.js';

const loading = ref(true);

const storage = ref({
  use: 0,
  total: 0,
  percent: 0,
  mode: 'auto',
  path_valid: true,
});

const displayPercent = computed(() => {
  const value = Number(storage.value.percent ?? 0);

  return Math.min(100, Math.max(0, Math.round(value)));
});

const barToneClass = computed(() => {
  if (displayPercent.value >= 90)
    return 'is-danger';

  if (displayPercent.value >= 75)
    return 'is-warning';

  return 'is-safe';
});

const ringProgress = computed(() => displayPercent.value);

function formatGb(value) {
  if (storage.value.size_pending)
    return '—';

  return Number(value ?? 0).toFixed(2);
}

async function loadStorage() {
  loading.value = true;

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
}

onMounted(loadStorage);
</script>

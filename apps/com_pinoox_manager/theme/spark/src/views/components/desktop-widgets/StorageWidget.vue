<template>
  <DraggableWidget class="storageWidget" initialX="8%" initialY="12%">
    <template #header>
      <div class="storageWidget__head">
        <div class="storageWidget__title">
          <span>فضای ذخیره‌سازی</span>
          <span class="storageWidget__mode-badge" :class="modeBadgeClass">
            {{ modeLabel }}
          </span>
        </div>
      </div>
    </template>

    <div v-if="storage.mode === 'manual' && !storage.path_valid" class="storageWidget__warning">
      مسیر انتخاب‌شده معتبر نیست. از کنترل پنل › ویجت‌ها تنظیمات را بررسی کنید.
    </div>

    <div class="storageWidget__stats">
      <div class="storageWidget__values">
        <strong>{{ formatGb(storage.use) }}</strong>
        <span>از {{ formatGb(storage.total) }} GB</span>
        <span v-if="storage.source_label" class="storageWidget__source">{{ storage.source_label }}</span>
      </div>
      <div class="storageWidget__percent-ring" :class="barToneClass">
        <span>{{ storage.percent }}%</span>
      </div>
    </div>

    <div class="storageWidget__bar" role="progressbar" :aria-valuenow="storage.percent" aria-valuemin="0" aria-valuemax="100">
      <div
          class="storageWidget__bar-fill"
          :class="barToneClass"
          :style="{ width: `${Math.min(storage.percent, 100)}%` }"
      ></div>
    </div>

    <div v-if="storage.resolved_path" class="storageWidget__path-row">
      <span class="storageWidget__path-label">مسیر</span>
      <p class="storageWidget__path" :title="storage.resolved_path">
        {{ storage.resolved_path }}
      </p>
    </div>
  </DraggableWidget>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import DraggableWidget from '../widgets/DraggableWidget.vue';
import { widgetAPI } from '@api/widget.js';
import { unwrapResponse } from '@utils/helpers/apiHelper.js';

const storage = ref({
  use: 0,
  total: 0,
  percent: 0,
  mode: 'auto',
  path: '',
  resolved_path: '',
  path_valid: true,
  source_label: 'دیسک سرور',
});

const barToneClass = computed(() => {
  if (storage.value.percent >= 90)
    return 'is-danger';

  if (storage.value.percent >= 75)
    return 'is-warning';

  return 'is-safe';
});

const modeLabel = computed(() => (storage.value.mode === 'manual' ? 'دستی' : 'خودکار'));

const modeBadgeClass = computed(() => (storage.value.mode === 'manual' ? 'is-manual' : 'is-auto'));

function formatGb(value) {
  return Number(value ?? 0).toFixed(2);
}

async function loadStorage() {
  const response = await widgetAPI.storage();
  const data = unwrapResponse(response) ?? {};

  storage.value = {
    ...storage.value,
    ...data,
  };
}

onMounted(loadStorage);
</script>

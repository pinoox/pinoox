<template>
  <div class="storageSettings">
    <p class="storageSettings__intro">
      پیش‌فرض: خودکار — فضای کل دیسک سرور نمایش داده می‌شود. برای محدودیت دستی، حالت «دستی» را انتخاب کنید.
    </p>

    <div class="storageSettings__mode-toggle" role="group" aria-label="حالت محاسبه">
      <button
          type="button"
          class="storageSettings__mode-btn"
          :class="{ 'is-active': form.mode === 'auto' }"
          @click="setMode('auto')"
      >
        خودکار
      </button>
      <button
          type="button"
          class="storageSettings__mode-btn"
          :class="{ 'is-active': form.mode === 'manual' }"
          @click="setMode('manual')"
      >
        دستی
      </button>
    </div>

    <div v-if="form.mode === 'auto'" class="storageSettings__auto-note">
      فضای کل دیسک سرور (مسیر ریشه پینوکس) در ویجت نمایش داده می‌شود.
    </div>

    <template v-else>
      <div class="storageSettings__browser">
        <div class="storageSettings__browser-toolbar">
          <button
              type="button"
              class="storageSettings__browser-up"
              :disabled="!browse.parent_path || browsing"
              title="پوشه بالاتر"
              @click="goUp"
          >
            ↑
          </button>
          <div class="storageSettings__browser-path" :title="browse.current_path">
            {{ browse.current_path || '...' }}
          </div>
        </div>

        <div v-if="browsing" class="storageSettings__browser-loading">در حال بارگذاری پوشه‌ها...</div>

        <ul v-else class="storageSettings__folder-list">
          <li v-if="!browse.folders?.length" class="storageSettings__folder-empty">
            زیرپوشه‌ای یافت نشد
          </li>
          <li v-for="folder in browse.folders" :key="folder.path">
            <button
                type="button"
                class="storageSettings__folder-item"
                :class="{ 'is-selected': form.path === folder.path }"
                @click="enterFolder(folder)"
            >
              <span class="storageSettings__folder-icon">📁</span>
              <span class="storageSettings__folder-name">{{ folder.name }}</span>
              <span class="storageSettings__folder-enter">›</span>
            </button>
          </li>
        </ul>

        <button
            type="button"
            class="storageSettings__select-current"
            :disabled="!browse.current_path || browsing"
            @click="selectCurrentFolder"
        >
          انتخاب این پوشه
        </button>
      </div>

      <label class="storageSettings__field">
        <span>مسیر انتخاب‌شده</span>
        <input
            :value="form.path"
            type="text"
            class="form-control storageSettings__input-readonly"
            dir="ltr"
            readonly
        />
      </label>

      <label class="storageSettings__field">
        <span>محدودیت (GB)</span>
        <input
            v-model.number="form.limit_gb"
            type="number"
            min="0.1"
            step="0.1"
            class="form-control"
        />
      </label>
    </template>

    <div class="storageSettings__actions">
      <button type="button" class="btn btn-primary" :disabled="saving || !canSave" @click="saveSettings">
        {{ saving ? 'در حال ذخیره...' : 'ذخیره تنظیمات فضای ذخیره‌سازی' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { widgetAPI } from '@api/widget.js';
import { unwrapResponse } from '@utils/helpers/apiHelper.js';

const saving = ref(false);
const browsing = ref(false);

const form = ref({
  mode: 'auto',
  path: '',
  limit_gb: 10,
});

const browse = ref({
  root_path: '',
  current_path: '',
  parent_path: null,
  folders: [],
});

const canSave = computed(() => {
  if (form.value.mode === 'auto')
    return true;

  return Boolean(form.value.path) && form.value.limit_gb > 0;
});

function applyStorage(data) {
  form.value.mode = data.mode ?? 'auto';
  form.value.path = data.resolved_path ?? data.path ?? data.default_path ?? '';
  form.value.limit_gb = data.limit_gb ?? 10;
}

async function loadBrowse(path) {
  browsing.value = true;

  try {
    const response = await widgetAPI.browseStorage(path || undefined);
    const data = unwrapResponse(response) ?? {};

    browse.value = {
      root_path: data.root_path ?? '',
      current_path: data.current_path ?? '',
      parent_path: data.parent_path ?? null,
      folders: data.folders ?? [],
    };

    if (!form.value.path && browse.value.current_path)
      form.value.path = browse.value.current_path;
  } finally {
    browsing.value = false;
  }
}

async function loadSettings() {
  const response = await widgetAPI.settings();
  const data = unwrapResponse(response) ?? {};

  applyStorage(data.storage ?? {});

  if (form.value.mode === 'manual')
    await loadBrowse(form.value.path);
}

async function setMode(mode) {
  form.value.mode = mode;

  if (mode === 'manual')
    await loadBrowse(form.value.path);
}

async function enterFolder(folder) {
  form.value.path = folder.path;
  await loadBrowse(folder.path);
}

async function goUp() {
  if (!browse.value.parent_path)
    return;

  form.value.path = browse.value.parent_path;
  await loadBrowse(browse.value.parent_path);
}

function selectCurrentFolder() {
  if (browse.value.current_path)
    form.value.path = browse.value.current_path;
}

async function saveSettings() {
  saving.value = true;

  try {
    await widgetAPI.saveStorageSettings({
      mode: form.value.mode,
      path: form.value.path,
      limit_gb: form.value.limit_gb,
    });
  } finally {
    saving.value = false;
  }
}

onMounted(loadSettings);
</script>

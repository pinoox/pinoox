<template>
  <div class="storageSettings">
    <p class="storageSettings__intro">
      پیش‌فرض: <strong>خودکار</strong> — فضای مصرف‌شده و کل دیسک سرور (ریشه پینوکس) در ویجت نمایش داده می‌شود.
      برای محاسبه دستی، حالت «پوشه» یا «دیتابیس» را انتخاب و ذخیره کنید.
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
          :class="{ 'is-active': form.mode === 'directory' }"
          @click="setMode('directory')"
      >
        پوشه
      </button>
      <button
          type="button"
          class="storageSettings__mode-btn"
          :class="{ 'is-active': form.mode === 'database' }"
          :disabled="!databaseAvailable"
          @click="setMode('database')"
      >
        دیتابیس
      </button>
    </div>

    <div v-if="form.mode === 'auto'" class="storageSettings__auto-note">
      تا زمانی که حالت «پوشه» یا «دیتابیس» را ذخیره نکنید، ویجت همیشه در حالت خودکار باقی می‌ماند.
    </div>

    <template v-else-if="form.mode === 'directory'">
      <p class="storageSettings__manual-note">
        مرور از <strong>ریشه پروژه</strong> شروع می‌شود؛ در صورت داشتن دسترسی می‌توانید به پوشه‌های بالاتر هم بروید.
        حجم مصرف‌شده از پوشه انتخابی و حجم کل از مقداری است که شما وارد می‌کنید.
      </p>

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
          <button
              type="button"
              class="storageSettings__browser-home"
              :disabled="browsing || browse.current_path === browse.project_root_path"
              title="ریشه پروژه"
              @click="goProjectRoot"
          >
            ⌂
          </button>
          <div class="storageSettings__browser-path" :title="browse.current_path">
            {{ browse.current_path || '...' }}
          </div>
        </div>

        <div v-if="browsing" class="storageSettings__browser-loading">در حال بارگذاری پوشه‌ها...</div>

        <ul v-else class="storageSettings__folder-list">
          <li v-if="!browse.folders?.length" class="storageSettings__folder-empty">
            زیرپوشه‌ای قابل دسترس یافت نشد
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
            :disabled="!browse.can_select_current || browsing"
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
            placeholder="یک پوشه از لیست بالا انتخاب کنید"
        />
      </label>

      <label class="storageSettings__field">
        <span>حجم کل (GB)</span>
        <input
            v-model.number="form.limit_gb"
            type="number"
            min="0.1"
            step="0.1"
            class="form-control"
            placeholder="مثلاً 50"
        />
      </label>
    </template>

    <template v-else-if="form.mode === 'database'">
      <p class="storageSettings__manual-note">
        حجم مصرف‌شده از مجموع ستون <code dir="ltr">file_size</code> در جدول
        <code dir="ltr">pinx_file</code> (همه اپ‌ها) محاسبه می‌شود — سریع و بدون اسکن پوشه.
      </p>

      <label class="storageSettings__field">
        <span>حجم کل (GB)</span>
        <input
            v-model.number="form.limit_gb"
            type="number"
            min="0.1"
            step="0.1"
            class="form-control"
            placeholder="مثلاً 50"
        />
      </label>
    </template>

    <p v-if="form.mode === 'database' && !databaseAvailable" class="storageSettings__error">
      جدول فایل‌ها در دیتابیس یافت نشد یا در دسترس نیست.
    </p>

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
const databaseAvailable = ref(true);

const form = ref({
  mode: 'auto',
  path: '',
  limit_gb: 10,
});

const browse = ref({
  root_path: '',
  project_root_path: '',
  current_path: '',
  parent_path: null,
  can_select_current: false,
  folders: [],
});

const canSave = computed(() => {
  if (form.value.mode === 'auto')
    return true;

  if (form.value.mode === 'database')
    return databaseAvailable.value && Number(form.value.limit_gb) > 0;

  return Boolean(form.value.path?.trim()) && Number(form.value.limit_gb) > 0;
});

function normalizeMode(mode) {
  if (mode === 'manual')
    return 'directory';

  return ['auto', 'directory', 'database'].includes(mode) ? mode : 'auto';
}

function applyStorage(data) {
  databaseAvailable.value = data.database_available !== false;
  const mode = normalizeMode(data.mode);

  form.value.mode = mode;

  if (mode === 'directory') {
    form.value.path = data.resolved_path ?? data.path ?? '';
    form.value.limit_gb = Number(data.limit_gb) > 0 ? Number(data.limit_gb) : 10;
    return;
  }

  if (mode === 'database') {
    form.value.path = '';
    form.value.limit_gb = Number(data.limit_gb) > 0 ? Number(data.limit_gb) : 10;
    return;
  }

  form.value.path = data.saved_path ?? '';
  form.value.limit_gb = Number(data.saved_limit_gb) > 0 ? Number(data.saved_limit_gb) : 10;
}

async function loadBrowse(path) {
  browsing.value = true;

  try {
    const response = await widgetAPI.browseStorage(path || undefined);
    const data = unwrapResponse(response) ?? {};

    browse.value = {
      root_path: data.root_path ?? data.project_root_path ?? '',
      project_root_path: data.project_root_path ?? data.root_path ?? '',
      current_path: data.current_path ?? '',
      parent_path: data.parent_path ?? null,
      can_select_current: Boolean(data.can_select_current),
      folders: data.folders ?? [],
    };
  } finally {
    browsing.value = false;
  }
}

async function loadSettings() {
  const response = await widgetAPI.settings();
  const data = unwrapResponse(response) ?? {};

  applyStorage(data.storage ?? {});

  if (form.value.mode === 'directory')
    await loadBrowse(form.value.path || undefined);
}

async function setMode(mode) {
  form.value.mode = mode;

  if (mode !== 'directory')
    return;

  const startPath = form.value.path?.trim() || undefined;
  await loadBrowse(startPath);
}

async function enterFolder(folder) {
  form.value.path = folder.path;
  await loadBrowse(folder.path);
}

async function goUp() {
  if (!browse.value.parent_path)
    return;

  await loadBrowse(browse.value.parent_path);
}

async function goProjectRoot() {
  const root = browse.value.project_root_path || browse.value.root_path;

  if (!root)
    return;

  form.value.path = root;
  await loadBrowse(root);
}

function selectCurrentFolder() {
  if (browse.value.current_path)
    form.value.path = browse.value.current_path;
}

async function saveSettings() {
  saving.value = true;

  try {
    const response = await widgetAPI.saveStorageSettings({
      mode: form.value.mode,
      path: form.value.path,
      limit_gb: form.value.limit_gb,
    });
    const data = unwrapResponse(response) ?? {};

    if (data.settings)
      applyStorage(data.settings);
  } finally {
    saving.value = false;
  }
}

onMounted(loadSettings);
</script>

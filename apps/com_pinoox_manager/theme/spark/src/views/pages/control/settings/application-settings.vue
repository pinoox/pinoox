<template>
  <Page title="تنظیمات اپلیکیشن" class="pageApplicationSettings">
    <PageSection title="باز کردن اپلیکیشن">
      <div class="space-y-6 max-w-2xl">
        <div class="flex items-center justify-between gap-4">
          <div>
            <h3 class="font-bold">حالت پیشرفته</h3>
            <p class="text-sm opacity-70">
              نوار آدرس، پنجره شناور، مینیمایز و مدیریت چند اپ همزمان
            </p>
          </div>
          <label class="switch">
            <input
                type="checkbox"
                :checked="isAdvanced"
                :disabled="saving"
                @change="onToggleMode($event.target.checked)"
            >
          </label>
        </div>

        <p v-if="isSimple" class="text-sm opacity-70">
          در حالت ساده فقط دکمه بازگشت، نام اپ و بازآوری صفحه نمایش داده می‌شود.
        </p>
      </div>
    </PageSection>
  </Page>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue';
import {useOptionsStore} from '@/stores/modules/options.js';
import {useAppViewWindowStore} from '@/stores/modules/appViewWindow.js';

const optionsStore = useOptionsStore();
const appViewWindow = useAppViewWindowStore();
const saving = ref(false);

const isAdvanced = computed(() => optionsStore.appViewMode === 'advanced');
const isSimple = computed(() => !isAdvanced.value);

onMounted(async () => {
  if (!optionsStore.isLoaded) {
    await optionsStore.load();
  }
});

async function onToggleMode(enabled) {
  if (saving.value) {
    return;
  }

  saving.value = true;

  try {
    const nextMode = enabled ? 'advanced' : 'simple';
    const saved = await optionsStore.changeAppViewMode(nextMode);

    if (saved === false) {
      return;
    }

    if (nextMode === 'simple') {
      appViewWindow.dismissAll();
    }
  } finally {
    saving.value = false;
  }
}
</script>

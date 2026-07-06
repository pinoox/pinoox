<template>
  <Page title="ظاهری" class="pageAppearance">
    <PageSection title="انتخاب تصویر زمینه">
      <template #actions>
        <button
            type="button"
            class="wallpaper-manage-btn"
            :class="{ 'is-active': isManagingWallpapers }"
            :disabled="isBusy"
            @click="toggleManageWallpapers"
        >
          <Icon :is="isManagingWallpapers ? saxIcon.close : saxIcon.setting" size="sm"/>
          <span>{{ isManagingWallpapers ? 'اتمام' : 'مدیریت' }}</span>
        </button>
      </template>

      <div v-if="!isManagingWallpapers" class="gallery flex flex-wrap gap-4">
        <button
            v-for="bg in backgrounds"
            :key="bg.id"
            type="button"
            class="gallery__image rounded-xs overflow-hidden rounded-lg transition-transform duration-300 ease-in-out hover:scale-105"
            :class="{
              active: selectedId === bg.id,
              'is-loading': changingBackgroundId === bg.id,
              'is-disabled': isBusy && changingBackgroundId !== bg.id,
            }"
            :disabled="isBusy"
            :aria-busy="changingBackgroundId === bg.id"
            @click="changeBackground(bg)"
        >
          <img :src="bg.url" :alt="`background-${bg.id}`" draggable="false">
          <span
              v-if="changingBackgroundId === bg.id"
              class="gallery__loading"
              aria-hidden="true"
          >
            <span class="gallery__spinner"></span>
          </span>
        </button>
      </div>

      <div v-else class="gallery-manage">
        <p class="gallery-manage__hint">برای حذف روی − ضربه بزنید · برای افزودن از + استفاده کنید</p>

        <div class="gallery flex flex-wrap gap-4 gallery--manage">
            <div
                v-for="bg in backgrounds"
                :key="bg.id"
                class="gallery-manage__item gallery-manage__item--jiggle"
                :class="{ 'is-loading': deletingBackgroundId === bg.id }"
                :style="jiggleStyle(bg.id)"
            >
              <img
                  :src="bg.url"
                  :alt="`background-${bg.id}`"
                  class="gallery-manage__thumb"
                  draggable="false"
              >
              <button
                  type="button"
                  class="gallery-manage__badge gallery-manage__badge--remove"
                  :disabled="isBusy"
                  aria-label="حذف تصویر زمینه"
                  @click="deleteWallpaper(bg)"
              >
                <span aria-hidden="true">−</span>
              </button>
              <span
                  v-if="deletingBackgroundId === bg.id"
                  class="gallery-manage__loading"
                  aria-hidden="true"
              >
                <span class="gallery__spinner"></span>
              </span>
            </div>

            <button
                type="button"
                class="gallery-manage__item gallery-manage__item--add gallery-manage__item--jiggle"
                :class="{ 'is-loading': uploadingWallpaper }"
                :style="jiggleStyle('add')"
                :disabled="isBusy"
                aria-label="افزودن تصویر زمینه"
                @click="openUpload"
            >
              <span v-if="uploadingWallpaper" class="gallery__spinner" aria-hidden="true"></span>
              <span v-else class="gallery-manage__plus" aria-hidden="true">+</span>
            </button>
        </div>

        <input
            ref="wallpaperInput"
            type="file"
            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            class="gallery__file-input"
            @change="handleUpload"
        >
      </div>
    </PageSection>

    <PageSection title="زمان قفل خودکار">
      <select v-model="lockTime" @change="saveLockTime" class="form-control">
        <option value="0">غیرفعال</option>
        <option value="10">10 دقیقه</option>
        <option value="20">20 دقیقه</option>
        <option value="30">30 دقیقه</option>
        <option value="60">60 دقیقه</option>
      </select>
    </PageSection>

    <PageSection title="زبان">
      <select v-model="currentLang" @change="saveLang" class="form-control">
        <option value="fa">فارسی</option>
        <option value="en">English</option>
      </select>
    </PageSection>
  </Page>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useBackground } from "@/views/composables/useBackground.js";
import { useOptionsStore } from "@/stores/modules/options.js";
import { saxIcon } from "@/const/icons.js";

const {
  backgrounds,
  selectedId,
  changingBackgroundId,
  uploadingWallpaper,
  deletingBackgroundId,
  isBusy,
  changeBackground,
  uploadWallpaper,
  deleteWallpaper,
} = useBackground();
const optionsStore = useOptionsStore();

const lockTime = ref(0);
const currentLang = ref('fa');
const wallpaperInput = ref(null);
const isManagingWallpapers = ref(false);

onMounted(async () => {
  if (!optionsStore.isLoaded)
    await optionsStore.load();
  lockTime.value = optionsStore.lock_time;
  currentLang.value = optionsStore.lang;
  window.addEventListener('keydown', onKeyDown);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeyDown);
});

function jiggleStyle(id) {
  const seed = String(id).split('').reduce((sum, char) => sum + char.charCodeAt(0), 0);
  const delay = (seed % 7) * 0.04;

  return { animationDelay: `${delay}s` };
}

function onKeyDown(event) {
  if (event.key === 'Escape' && isManagingWallpapers.value)
    isManagingWallpapers.value = false;
}

const toggleManageWallpapers = () => {
  if (isBusy.value)
    return;
  isManagingWallpapers.value = !isManagingWallpapers.value;
};

const openUpload = () => {
  if (isBusy.value)
    return;
  wallpaperInput.value?.click();
};

const handleUpload = async (event) => {
  const file = event.target.files?.[0];
  event.target.value = '';
  if (!file)
    return;
  await uploadWallpaper(file, { select: false });
};

const saveLockTime = async () => {
  await optionsStore.changeLockTime(Number(lockTime.value));
};

const saveLang = async () => {
  await optionsStore.changeLang(currentLang.value);
};
</script>

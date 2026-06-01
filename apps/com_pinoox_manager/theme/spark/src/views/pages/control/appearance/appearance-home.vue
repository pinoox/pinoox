<template>
  <Page title="ظاهری" class="pageAppearance">
    <PageSection title="انتخاب تصویر زمینه">
      <div class="gallery flex flex-wrap gap-4">
        <div
            v-for="bg in backgrounds"
            :key="bg.id"
            @click="changeBackground(bg)"
            class="gallery__image rounded-xs cursor-pointer overflow-hidden rounded-lg transition-transform duration-300 ease-in-out hover:scale-105"
            :class="{ active: selectedId === bg.id }"
        >
          <img :src="bg.url" :alt="`background-${bg.id}`">
        </div>
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
import { ref, onMounted } from "vue";
import { useBackground } from "@/views/composables/useBackground.js";
import { useOptionsStore } from "@/stores/modules/options.js";

const { backgrounds, selectedId, changeBackground } = useBackground();
const optionsStore = useOptionsStore();

const lockTime = ref(0);
const currentLang = ref('fa');

onMounted(async () => {
  if (!optionsStore.isLoaded)
    await optionsStore.load();
  lockTime.value = optionsStore.lock_time;
  currentLang.value = optionsStore.lang;
});

const saveLockTime = async () => {
  await optionsStore.changeLockTime(Number(lockTime.value));
};

const saveLang = async () => {
  await optionsStore.changeLang(currentLang.value);
};
</script>

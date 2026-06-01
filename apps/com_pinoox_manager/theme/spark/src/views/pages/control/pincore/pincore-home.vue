<template>
  <Page title="درباره پینوکس" class="pageAbout">
    <PageSection>
      <div class="flex flex-col items-center gap-4 text-center">
        <img src="@/assets/media/pinoox/logo.png" alt="pinoox" class="w-24 h-24"/>
        <h2 class="text-2xl font-bold">پینوکس <span class="text-sm opacity-70">{{ pinoox.client?.version_name }}</span></h2>
        <p class="max-w-lg opacity-80">پینوکس یک فریم‌ورک PHP ماژولار برای ساخت اپلیکیشن‌های وب است.</p>

        <div class="flex flex-col gap-3 mt-4">
          <Button
              :label="isLoadingCheck ? 'در حال بررسی...' : 'بررسی بروزرسانی'"
              variant="primary"
              :is-loading="isLoadingCheck"
              @click="checkUpdate"
          />
          <Button
              v-if="pinoox.isNewVersion"
              label="نصب بروزرسانی"
              variant="light"
              outline
              :is-loading="isLoadingUpdate"
              @click="installUpdate"
          />
          <p v-if="pinoox.isNewVersion" class="text-yellow-300">
            نسخه جدید {{ pinoox.server?.version_name }} در دسترس است
          </p>
          <p v-else-if="checked" class="opacity-70">پینوکس شما به‌روز است</p>
        </div>
      </div>
    </PageSection>
  </Page>
</template>

<script setup>
import { ref } from "vue";
import { unwrapResponse } from "@utils/helpers/apiHelper.js";
import { updateAPI } from "@api/update.js";

const pinoox = ref({ client: {}, server: {}, isNewVersion: false });
const isLoadingCheck = ref(false);
const isLoadingUpdate = ref(false);
const checked = ref(false);

const checkUpdate = async () => {
  isLoadingCheck.value = true;
  try {
    const response = await updateAPI.checkVersion(true);
    pinoox.value = unwrapResponse(response) ?? {};
    checked.value = true;
  } finally {
    isLoadingCheck.value = false;
  }
};

const installUpdate = async () => {
  isLoadingUpdate.value = true;
  try {
    const response = await updateAPI.install();
    pinoox.value = unwrapResponse(response) ?? {};
    checked.value = true;
  } finally {
    isLoadingUpdate.value = false;
  }
};
</script>

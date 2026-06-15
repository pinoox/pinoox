<template>
  <Page :title="app?.name || 'جزئیات'" class="pageMarketDetails">
    <template #toolbar>
      <Menu :icon="saxIcon.back" label="بازگشت" @click="$router.push('/market')"/>
    </template>

    <div v-if="app" class="max-w-2xl space-y-4">
      <div class="flex items-center gap-4">
        <AppIcon v-bind="appIconProps(app)" size="xl"/>
        <div>
          <h2 class="text-2xl font-bold">{{ app.name }}</h2>
          <p class="opacity-70">{{ app.developer }}</p>
        </div>
      </div>
      <p>{{ app.description }}</p>
      <Button
          v-if="app.state === 'download'"
          label="دانلود"
          variant="primary"
          :is-loading="isDownloading"
          @click="download"
      />
      <Button
          v-else-if="app.state === 'install'"
          label="نصب"
          variant="primary"
          :is-loading="isInstalling"
          @click="install"
      />
      <p v-else-if="app.state === 'installed'" class="text-green-400">نصب شده</p>
    </div>
  </Page>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { marketAPI } from "@api/market.js";
import { appAPI } from "@api/app.js";
import { accountAPI } from "@api/account.js";
import { saxIcon } from "@/const/icons.js";
import { useAppStore } from "@/stores/modules/app.js";
import { appIconProps } from "@utils/helpers/appIconProps.js";
import { unwrapResponse } from "@utils/helpers/apiHelper.js";

const route = useRoute();
const router = useRouter();
const appStore = useAppStore();
const app = ref(null);
const isDownloading = ref(false);
const isInstalling = ref(false);

onMounted(async () => {
  const response = await marketAPI.getOneApp(route.params.package_name);
  app.value = unwrapResponse(response);
});

const getAuth = async () => {
  const connect = await accountAPI.getConnectData();
  const data = unwrapResponse(connect) ?? connect.data ?? {};
  return data?.token_key ? { token: data.token_key } : {};
};

const download = async () => {
  isDownloading.value = true;
  try {
    const auth = await getAuth();
    await marketAPI.downloadRequest(route.params.package_name, auth);
    const response = await marketAPI.getOneApp(route.params.package_name);
    app.value = unwrapResponse(response);
  } finally {
    isDownloading.value = false;
  }
};

const install = async () => {
  isInstalling.value = true;
  try {
    await appAPI.installPackage(`${route.params.package_name}.pin`);
    await appStore.getApps();
    router.push('/control/apps');
  } finally {
    isInstalling.value = false;
  }
};
</script>

<template>
  <PageSection title="تنظیمات">
    <div class="space-y-6 max-w-2xl">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-bold">مسیریابی چندگانه</h3>
          <p class="text-sm opacity-70">امکان تعریف چند مسیر برای این اپ</p>
        </div>
        <label class="switch">
          <input type="checkbox" :checked="routerMode === 'multiple'" @change="toggle"/>
        </label>
      </div>
    </div>
  </PageSection>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { appAPI } from "@api/app.js";
import { unwrapResponse } from "@utils/helpers/apiHelper.js";
import { resolveRouterMode } from "@utils/helpers/appRoutePolicy.js";

const props = defineProps({
  packageName: String,
});

const config = ref({ router: { type: 'multiple' } });

const routerMode = computed(() => resolveRouterMode(config.value));

onMounted(async () => {
  const response = await appAPI.getConfig(props.packageName);
  config.value = unwrapResponse(response) ?? {};
});

const toggle = async () => {
  await appAPI.setConfig(props.packageName, 'router', routerMode.value);
  const response = await appAPI.getConfig(props.packageName);
  config.value = unwrapResponse(response) ?? {};
};
</script>

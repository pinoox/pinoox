<template>
  <PageSection title="قالب‌ها">
    <div v-if="templates.length" class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="tpl in templates" :key="tpl.folder" class="bg-white/5 rounded-lg overflow-hidden">
        <img :src="tpl.cover" :alt="tpl.template_name" class="w-full h-32 object-cover"/>
        <div class="p-3">
          <h3 class="font-bold">{{ tpl.template_name }}</h3>
          <div class="flex gap-2 mt-2">
            <Button v-if="!tpl.activate" label="فعال‌سازی" size="sm" variant="primary" @click="activate(tpl.folder)"/>
            <span v-else class="text-green-400 text-sm">فعال</span>
            <Button label="حذف" size="sm" variant="dark" outline @click="remove(tpl.folder)"/>
          </div>
        </div>
      </div>
    </div>
    <PageEmpty v-else title="قالبی یافت نشد"/>
  </PageSection>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { templateAPI } from "@api/template.js";

const props = defineProps({ packageName: String });
const templates = ref([]);

const load = async () => {
  const response = await templateAPI.get(props.packageName);
  templates.value = response.data ?? [];
};

onMounted(load);

const activate = async (folder) => {
  await templateAPI.set(props.packageName, folder);
  await load();
};

const remove = async (folder) => {
  await templateAPI.remove(props.packageName, folder);
  await load();
};
</script>

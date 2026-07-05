<template>
  <PageSection title="قالب‌ها">
    <div v-if="isLoading && !hasCachedData" class="appManagerSectionLoading">
      <WidgetLoading/>
    </div>

    <div
        v-else
        class="appManagerSection"
        :class="{ 'is-refreshing': isRefreshing }"
    >
      <div v-if="isRefreshing" class="appManagerSection__refresh" aria-hidden="true">
        <WidgetLoading/>
      </div>

      <div v-if="templates.length" class="appTemplates">
        <article v-for="tpl in templates" :key="tpl.folder" class="appTemplates__card">
          <img :src="tpl.cover" :alt="tpl.template_name" class="appTemplates__cover"/>
          <div class="appTemplates__body">
            <h3 class="appTemplates__title">{{ tpl.template_name }}</h3>
            <p v-if="tpl.activate" class="appTemplates__activeNote">{{ translate('template_active_no_delete') }}</p>

            <div class="appTemplates__actions">
              <Button
                  v-if="!tpl.activate"
                  :label="translate('template_activate_button')"
                  size="sm"
                  variant="primary"
                  :is-loading="activatingFolder === tpl.folder"
                  :is-disabled="!!activatingFolder || removingFolder"
                  @click="activate(tpl.folder)"
              />
              <span v-else class="appTemplates__activeBadge">{{ translate('template_active_badge') }}</span>

              <Button
                  v-if="!tpl.activate"
                  :label="translate('template_delete_button')"
                  size="sm"
                  variant="danger"
                  :is-loading="removingFolder === tpl.folder"
                  :is-disabled="!!activatingFolder || !!removingFolder"
                  @click="remove(tpl.folder)"
              />
            </div>
          </div>
        </article>
      </div>

      <PageEmpty v-else title="قالبی یافت نشد"/>
    </div>
  </PageSection>
</template>

<script setup>
import {computed, ref} from 'vue';
import {templateAPI} from '@api/template.js';
import {unwrapResponse} from '@utils/helpers/apiHelper.js';
import {translate} from '@utils/helpers/managerLang.js';
import WidgetLoading from '@/views/components/desktop-widgets/WidgetLoading.vue';
import {useAppManagerSectionData} from '@/views/composables/useAppManagerSectionData.js';

const props = defineProps({packageName: String});

const packageName = computed(() => props.packageName);
const activatingFolder = ref('');
const removingFolder = ref('');

const {
  items: templates,
  isLoading,
  isRefreshing,
  hasCachedData,
  reload,
} = useAppManagerSectionData('templates', packageName, async (name) => {
  const response = await templateAPI.get(name);
  return unwrapResponse(response) ?? [];
});

async function activate(folder) {
  if (activatingFolder.value || removingFolder.value) {
    return;
  }

  activatingFolder.value = folder;

  try {
    await templateAPI.set(props.packageName, folder);
    await reload();
  } finally {
    activatingFolder.value = '';
  }
}

async function remove(folder) {
  if (activatingFolder.value || removingFolder.value) {
    return;
  }

  const target = templates.value.find((item) => item.folder === folder);

  if (target?.activate) {
    return;
  }

  if (!window.confirm(translate('template_delete_confirm'))) {
    return;
  }

  removingFolder.value = folder;

  try {
    await templateAPI.remove(props.packageName, folder);
    await reload();
  } finally {
    removingFolder.value = '';
  }
}
</script>

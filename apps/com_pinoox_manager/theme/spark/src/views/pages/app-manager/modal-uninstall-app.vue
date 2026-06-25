<template>
  <SimpleModal :title="translate('are_you_sure_delete_app')" size="sm" class="modalAppUninstall">
    <div
        class="modalAppUninstall__body"
        :class="{
          'is-deleting': isDeleting,
          'is-done': isDone,
        }"
    >
      <div class="modalAppUninstall__hero" aria-hidden="true">
        <span class="modalAppUninstall__iconRing"/>
        <span class="modalAppUninstall__iconRing modalAppUninstall__iconRing--delayed"/>
        <span class="modalAppUninstall__iconWrap">
          <Icon :is="saxIcon.remove" class="modalAppUninstall__icon"/>
        </span>
      </div>

      <p class="modalAppUninstall__lead">{{ translate('app_uninstall_lead') }}</p>
      <p class="modalAppUninstall__hint">{{ translate('app_uninstall_hint') }}</p>

      <div class="modalAppUninstall__preview">
        <AppIcon v-bind="appIconProps(app)" size="md"/>
        <div class="modalAppUninstall__previewText">
          <strong>{{ app?.name || packageName }}</strong>
          <span dir="ltr">{{ packageName }}</span>
        </div>
      </div>

      <p v-if="isDeleting" class="modalAppUninstall__status" role="status" aria-live="polite">
        <span class="modalAppUninstall__statusDot"/>
        {{ translate('app_uninstall_progress') }}
      </p>
    </div>

    <template #footer>
      <Button
          :label="translate('cancel')"
          variant="dark"
          outline
          :is-disabled="isDeleting || isDone"
          @click="closeModal"
      />
      <Button
          :label="translate('app_uninstall_confirm')"
          variant="danger"
          :is-loading="isDeleting"
          :is-disabled="isDone"
          @click="confirmUninstall"
      />
    </template>
  </SimpleModal>
</template>

<script setup>
defineOptions({modalGroup: 'default'});

import {nextTick, ref} from 'vue';
import {closeModal, useModalContext} from '@kolirt/vue-modal';
import {saxIcon} from '@/const/icons.js';
import Button from '@/views/components/widgets/Button.vue';
import Icon from '@/views/components/widgets/Icon.vue';
import SimpleModal from '@/views/components/commons/SimpleModal.vue';
import AppIcon from '@/views/components/widgets/AppIcon.vue';
import {appAPI} from '@api/app.js';
import {useAppStore} from '@/stores/modules/app.js';
import {appIconProps} from '@utils/helpers/appIconProps.js';
import {translate} from '@utils/helpers/managerLang.js';
import {HTTP_ALERT_SILENT} from '@utils/helpers/alertHelper.js';
import {unwrapResponse} from '@utils/helpers/apiHelper.js';
import {resolveApiFailure} from '@utils/apiEnvelope.js';
import {toastError} from '@utils/helpers/toastHelper.js';

const props = defineProps({
  app: {
    type: Object,
    required: true,
  },
  packageName: {
    type: String,
    required: true,
  },
});

const {confirm} = useModalContext();
const appStore = useAppStore();

const isDeleting = ref(false);
const isDone = ref(false);

const confirmUninstall = async () => {
  if (isDeleting.value || isDone.value) {
    return;
  }

  isDeleting.value = true;
  await nextTick();

  try {
    const response = await appAPI.remove(props.packageName, HTTP_ALERT_SILENT);
    unwrapResponse(response);
    appStore.deleteAppByPackage(props.packageName);
    isDone.value = true;
    await new Promise((resolve) => setTimeout(resolve, 420));
    confirm({uninstalled: true});
  } catch (error) {
    toastError(resolveApiFailure(error));
  } finally {
    isDeleting.value = false;
  }
};
</script>

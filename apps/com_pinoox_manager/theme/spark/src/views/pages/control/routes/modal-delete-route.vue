<template>
  <SimpleModal :title="translate('route_delete_title')" size="sm" class="modalRoutesDelete">
    <div
        class="modalRoutesDelete__body"
        :class="{
          'is-deleting': isDeleting,
          'is-done': isDone,
        }"
    >
      <div class="modalRoutesDelete__hero" aria-hidden="true">
        <span class="modalRoutesDelete__iconRing"/>
        <span class="modalRoutesDelete__iconRing modalRoutesDelete__iconRing--delayed"/>
        <span class="modalRoutesDelete__iconWrap">
          <Icon :is="saxIcon.remove" class="modalRoutesDelete__icon"/>
        </span>
      </div>

      <p class="modalRoutesDelete__lead">{{ translate('route_delete_lead') }}</p>
      <p class="modalRoutesDelete__hint">{{ translate('route_delete_hint') }}</p>

      <div class="modalRoutesDelete__preview" dir="ltr">
        <div class="modalRoutesDelete__url">
          <span class="modalRoutesDelete__url-origin">{{ siteOrigin }}</span>
          <span class="modalRoutesDelete__url-path">{{ routeUrlSuffix(route.path) }}</span>
        </div>
        <div class="modalRoutesDelete__arrow" aria-hidden="true">→</div>
        <div class="modalRoutesDelete__app">
          <AppBrandIcon
              v-if="isManagerBrandApp(routeApp, route?.package)"
              v-bind="managerBrandIconProps(routeApp, route?.package)"
              size="xs"
          />
          <AppIcon
              v-else
              v-bind="resolveRouteAppIconProps(routeApp, route?.package)"
              size="xs"
          />
          <span class="modalRoutesDelete__app-name">{{ appDisplayName }}</span>
        </div>
      </div>

      <p v-if="isDeleting" class="modalRoutesDelete__status" role="status" aria-live="polite">
        <span class="modalRoutesDelete__statusDot"/>
        {{ translate('route_delete_progress') }}
      </p>
    </div>

    <template #footer>
      <Button
          :label="translate('route_delete_cancel')"
          variant="dark"
          outline
          :is-disabled="isDeleting || isDone"
          @click="closeModal"
      />
      <Button
          :label="translate('route_delete_confirm')"
          variant="danger"
          :is-loading="isDeleting"
          :is-disabled="isDone"
          @click="confirmDelete"
      />
    </template>
  </SimpleModal>
</template>

<script setup>
defineOptions({modalGroup: 'default'});

import {computed, nextTick, ref} from 'vue';
import {closeModal, useModalContext} from '@kolirt/vue-modal';
import { getUrl } from '@/boot.js';
import {saxIcon} from '@/const/icons.js';
import Button from '@/views/components/widgets/Button.vue';
import {useAppStore} from '@/stores/modules/app.js';
import {resolveRouteAppIconProps} from '@utils/helpers/appIconProps.js';
import {resolveAppDisplayLabel, isManagerBrandApp, managerBrandIconProps} from '@utils/helpers/appDisplayLabel.js';
import {translate} from '@utils/helpers/managerLang.js';
import {formatSiteOriginForDisplay} from '@utils/helpers/siteUrlHelper.js';
import {routerAPI} from '@api/router.js';
import {HTTP_ALERT_SILENT} from '@utils/helpers/alertHelper.js';
import {unwrapResponse} from '@utils/helpers/apiHelper.js';
import {resolveApiFailure} from '@utils/apiEnvelope.js';
import {toastError} from '@utils/helpers/toastHelper.js';

const props = defineProps({
  route: {
    type: Object,
    required: true,
  },
});

const {confirm} = useModalContext();
const appStore = useAppStore();

const isDeleting = ref(false);
const isDone = ref(false);

const siteOrigin = formatSiteOriginForDisplay(getUrl().SITE);

const routeApp = computed(() => appStore.fetchAppByPackage(props.route?.package));

const appDisplayName = computed(() => resolveAppDisplayLabel(routeApp.value, props.route?.package));

function routeUrlSuffix(path) {
  if (path === '/') return '/';
  return path.startsWith('/') ? path : `/${path}`;
}

const confirmDelete = async () => {
  if (isDeleting.value || isDone.value) {
    return;
  }

  isDeleting.value = true;
  await nextTick();

  try {
    const response = await routerAPI.remove({path: props.route.path}, HTTP_ALERT_SILENT);
    unwrapResponse(response);
    isDone.value = true;
    await new Promise((resolve) => setTimeout(resolve, 420));
    confirm();
  } catch (error) {
    toastError(resolveApiFailure(error));
  } finally {
    isDeleting.value = false;
  }
};
</script>

<template>
  <div class="appDetails">
    <section class="appDetails__stats">
      <article v-for="item in statItems" :key="item.label" class="appDetails__stat">
        <span class="appDetails__statLabel">{{ item.label }}</span>
        <span class="appDetails__statValue" :dir="item.ltr ? 'ltr' : undefined">{{ item.value }}</span>
      </article>
    </section>

    <section v-if="app?.description" class="appDetails__card">
      <h3 class="appDetails__cardTitle">توضیحات</h3>
      <p class="appDetails__description">{{ app.description }}</p>
    </section>

    <section v-if="badges.length" class="appDetails__badges">
      <span v-for="badge in badges" :key="badge.label" class="appDetails__badge" :class="badge.class">
        {{ badge.label }}
      </span>
    </section>

    <section v-if="routes.length" class="appDetails__card">
      <h3 class="appDetails__cardTitle">مسیرهای فعال</h3>
      <ul class="appDetails__routes">
        <li v-for="route in routesPreview" :key="route.path" dir="ltr">
          <code>{{ route.path }}</code>
        </li>
      </ul>
      <p v-if="routes.length > routesPreview.length" class="appDetails__routesMore">
        +{{ routes.length - routesPreview.length }} مسیر دیگر
      </p>
    </section>

    <section class="appDetails__actions">
      <Button
          label="اجرای اپ"
          variant="primary"
          :icon="saxIcon.externalLink"
          @click="openApp"
      />
      <Button
          label="تنظیمات"
          variant="dark"
          outline
          :icon="saxIcon.setting"
          @click="goConfig"
      />
      <Button
          v-if="hasTemplates"
          label="قالب‌ها"
          variant="dark"
          outline
          :icon="saxIcon.appearance"
          @click="goTemplates"
      />
    </section>

    <section v-if="isSystemApp" class="appDetails__notice appDetails__notice--info">
      <Icon :is="saxIcon.notifyInfo" size="sm"/>
      <p>این اپلیکیشن سیستمی است و از طریق پنل قابل حذف نیست.</p>
    </section>

    <section v-else class="appDetails__danger">
      <div class="appDetails__dangerHead">
        <Icon :is="saxIcon.remove" size="sm"/>
        <h3>حذف اپلیکیشن</h3>
      </div>
      <p>
        با حذف، فایل‌های اپ، جداول دیتابیس (rollback)، مسیرها و کش pinker پاک می‌شوند.
        برای نصب مجدد باید بسته <strong>.pinx</strong> را دوباره نصب کنید.
      </p>
      <Button
          label="حذف اپلیکیشن"
          variant="danger"
          outline
          :icon="saxIcon.remove"
          @click="openUninstallModal"
      />
    </section>
  </div>
</template>

<script setup>
import {computed} from 'vue';
import {openModal} from '@kolirt/vue-modal';
import {saxIcon} from '@/const/icons.js';
import Icon from '@/views/components/widgets/Icon.vue';
import {resolveRouterMode} from '@utils/helpers/appRoutePolicy.js';
import {useGlobalRouter} from '@/views/composables/useGlobalRouter.js';
import {useControlPanelNavigation} from '@/views/composables/useControlPanelNavigation.js';
import {translate} from '@utils/helpers/managerLang.js';
import {toastSuccess} from '@utils/helpers/toastHelper.js';
import ModalUninstallApp from '@/views/pages/app-manager/modal-uninstall-app.vue';

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

const globalRouter = useGlobalRouter();
const {pushAppManager, pushControlPath} = useControlPanelNavigation();

const isSystemApp = computed(() => !!(props.app?.sys_app ?? props.app?.['sys-app']));

const routes = computed(() => {
  const list = props.app?.routes;

  return Array.isArray(list) ? list : [];
});

const routesPreview = computed(() => routes.value.slice(0, 5));

const hasTemplates = computed(() => !isSystemApp.value);

const routerModeLabel = computed(() => {
  const mode = resolveRouterMode(props.app);

  return mode === 'single' ? 'تک‌مسیره' : 'چندمسیره';
});

const statItems = computed(() => [
  {label: 'نسخه', value: props.app?.version || '—', ltr: false},
  {label: 'کد نسخه', value: props.app?.version_code ?? '—', ltr: true},
  {label: 'توسعه‌دهنده', value: props.app?.developer || '—', ltr: false},
  {label: 'پکیج', value: props.packageName, ltr: true},
  {label: 'مسیریابی', value: routerModeLabel.value, ltr: false},
  {label: 'مسیرها', value: String(routes.value.length), ltr: true},
]);

const badges = computed(() => {
  const list = [];

  if (isSystemApp.value) {
    list.push({label: 'اپ سیستمی', class: 'is-system'});
  }

  if (props.app?.hidden) {
    list.push({label: 'مخفی', class: 'is-muted'});
  }

  if (props.app?.dock === false) {
    list.push({label: 'بدون داک', class: 'is-muted'});
  }

  return list;
});

function openApp() {
  globalRouter.push({name: 'app-view', params: {package_name: props.packageName}});
}

function goConfig() {
  pushAppManager(props.packageName, 'config');
}

function goTemplates() {
  pushAppManager(props.packageName, 'templates');
}

function openUninstallModal() {
  openModal(ModalUninstallApp, {
    props: {
      app: props.app,
      packageName: props.packageName,
    },
  }).then((result) => {
    if (result?.uninstalled) {
      toastSuccess(translate('delete_successfully'));
      pushControlPath('/control/apps');
    }
  }).catch(() => {});
}
</script>

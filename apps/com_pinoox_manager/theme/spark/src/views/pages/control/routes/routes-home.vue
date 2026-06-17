<template>
  <Page title="مسیریابی" class="pageRoutes">
    <template #toolbar>
      <Menu @click="openModalAddEditRoute()" :icon="saxIcon.add" label="افزودن"/>
      <Menu @click="openGuideModal" :icon="saxIcon.guide" label="راهنما"/>
    </template>

    <div class="routeMap">
      <div class="routeMap__intro">
        <div class="routeMap__intro-iconWrap" aria-hidden="true">
          <Icon :is="saxIcon.routes" class="routeMap__intro-icon"/>
        </div>
        <div class="routeMap__intro-copy">
          <p class="routeMap__intro-title">هر آدرس، یک اپلیکیشن</p>
          <p class="routeMap__intro-desc">
            مسیریابی هستهٔ پینوکس است — با تعریف مسیر، مشخص می‌کنید کاربر با باز کردن هر URL به کدام اپ هدایت شود.
          </p>
        </div>
      </div>

      <div v-if="sortedRoutes.length" class="routeMap__board">
        <div class="routeMap__site">
          <span class="routeMap__site-pulse" aria-hidden="true"/>
          <span class="routeMap__site-label" dir="ltr">{{ currentSite }}</span>
          <span class="routeMap__site-caption">دامنهٔ فعال</span>
        </div>

        <ul class="routeMap__list">
          <li
              v-for="(route, index) in sortedRoutes"
              :key="route.path ?? index"
              class="routeCard"
              :class="routeCardClass(route)"
          >
            <div class="routeCard__pathBlock">
              <div class="routeCard__badges">
                <span v-if="isDefaultRoute(route)" class="routeCard__badge routeCard__badge--default">
                  پیش‌فرض
                </span>
                <span v-if="route.is_lock" class="routeCard__badge routeCard__badge--lock">
                  <Icon :is="saxIcon.lock" size="xs"/>
                  <span>سیستمی</span>
                </span>
              </div>

              <div class="routeCard__url" dir="ltr">
                <span class="routeCard__url-origin">{{ currentSite }}</span>
                <span class="routeCard__url-path">{{ routeUrlSuffix(route.path) }}</span>
              </div>
            </div>

            <div class="routeCard__connector" aria-hidden="true">
              <span class="routeCard__connector-line"/>
              <Icon :is="saxIcon.arrowLeft" class="routeCard__connector-arrow"/>
            </div>

            <button
                type="button"
                class="routeCard__app"
                :title="`تغییر اپلیکیشن ${routeApp(route)?.name ?? route.package}`"
                @click="openRouteEditor(route)"
            >
              <AppIcon v-bind="appIconProps(routeApp(route))" size="sm"/>
              <span class="routeCard__app-name">{{ routeApp(route)?.name ?? route.package }}</span>
              <span class="routeCard__app-package" dir="ltr">{{ route.package }}</span>
            </button>

            <div v-if="canManageRoute(route)" class="routeCard__actions">
              <button
                  type="button"
                  class="routeCard__action routeCard__action--edit"
                  title="ویرایش مسیر"
                  @click="editRoute(route)"
              >
                <Icon :is="saxIcon.edit" class="pageRoutes__action-icon" size="sm"/>
              </button>
              <button
                  v-if="!isDefaultRoute(route)"
                  type="button"
                  class="routeCard__action routeCard__action--delete"
                  title="حذف مسیر"
                  @click="deleteRoute(route.path)"
              >
                <Icon :is="saxIcon.remove" class="pageRoutes__action-icon" size="sm"/>
              </button>
            </div>
          </li>

          <li>
            <button type="button" class="routeCard routeCard--add" @click="openModalAddEditRoute()">
              <span class="routeCard__add-icon" aria-hidden="true">+</span>
              <span class="routeCard__add-label">افزودن مسیر جدید</span>
              <span class="routeCard__add-hint">مثلاً {{ currentSite }}/shop</span>
            </button>
          </li>
        </ul>
      </div>

      <PageEmpty
          v-else
          title="هیچ مسیری ثبت نشده است"
          description="برای افزودن مسیر جدید، روی دکمه افزودن کلیک کنید."
          :icon="saxIcon.routes"
      />
    </div>
  </Page>
</template>

<script setup>
import {computed, ref} from 'vue';
import { getUrl } from '@/boot.js';
import {saxIcon} from '@/const/icons.js';
import {openModal} from '@kolirt/vue-modal';
import ModalGuide from '@views/components/commons/ModalGuide.vue';
import ModalAddEditRoute from '@views/pages/control/routes/modal-add-edit-route.vue';
import {routerAPI} from "@api/router.js";
import {useRouteStore} from "@/stores/modules/route.js";
import {useAppStore} from "@/stores/modules/app.js";
import {appIconProps} from "@utils/helpers/appIconProps.js";

const routeStore = useRouteStore();
const appStore = useAppStore();

const currentSite = getUrl().SITE;

const sortedRoutes = computed(() => {
  return [...routeStore.routeList].sort((a, b) => {
    if (a.path === '/') return -1;
    if (b.path === '/') return 1;
    return a.path.length - b.path.length;
  });
});

const guideMessage = ref(
    `<p>در <strong>پینوکس</strong> می‌توانید مسیرهایی را تعریف کنید تا هر مسیر، اپلیکیشن خاصی را نمایش دهد.</p>` +
    `<p>به عنوان مثال، اگر کاربر <code>${currentSite}/shop</code> را وارد کند، اپلیکیشن فروشگاه باز می‌شود.</p>` +
    `<h3>مثال مسیرها:</h3>` +
    `<ul>` +
    `    <li><code>${currentSite}/</code> → اپلیکیشن پیش‌فرض (Welcome)</li>` +
    `    <li><code>${currentSite}/manager</code> → پنل مدیریت</li>` +
    `    <li><code>${currentSite}/shop</code> → فروشگاه</li>` +
    `</ul>` +
    `<p>با این روش، کاربران به‌صورت خودکار به اپلیکیشن‌های مرتبط هدایت می‌شوند.</p>`
);

function isDefaultRoute(route) {
  return route?.path === '/';
}

function routeUrlSuffix(path) {
  if (path === '/') return '/';
  return path.startsWith('/') ? path : `/${path}`;
}

function routeCardClass(route) {
  return {
    'routeCard--default': isDefaultRoute(route),
    'routeCard--locked': route.is_lock,
  };
}

function canManageRoute(route) {
  return !route.is_lock || isDefaultRoute(route);
}

function openGuideModal() {
  void openModal(ModalGuide, {props: {message: guideMessage.value}}).catch(() => {});
}

function openModalAddEditRoute(route = null) {
  if (route?.path === '/') {
    openModalEditApp(route);
  } else {
    openModal(ModalAddEditRoute, {props: {payload: route}}).then(() => {
    }).catch(() => {
    });
  }
}

function openModalEditApp(route = null) {
  openModal(ModalAddEditRoute, {props: {payload: route, hasSelectApp: true}}).then(() => {
  }).catch(() => {
  });
}

function openRouteEditor(route) {
  if (isDefaultRoute(route)) {
    openModalEditApp(route);
    return;
  }
  if (!route.is_lock) {
    editRoute(route);
  }
}

function editRoute(route) {
  openModalAddEditRoute(route);
}

function deleteRoute(path) {
  routerAPI.remove({
    path: path,
  }).then(() => {
    routeStore.deleteRouteByPath(path);
  });
}

function routeApp(route) {
  return appStore.fetchAppByPackage(route?.package);
}

</script>

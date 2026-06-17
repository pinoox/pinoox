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
          <p class="routeMap__intro-title">با باز کردن هر آدرس، یک برنامه نمایش داده می‌شود</p>
          <p class="routeMap__intro-desc">
            مثلاً وقتی کسی آدرس سایت شما را باز می‌کند، برنامه‌ای که اینجا انتخاب کرده‌اید برایش نمایش داده می‌شود.
          </p>
        </div>
      </div>

      <div v-if="sortedRoutes.length" class="routeMap__board" dir="ltr">
        <div class="routeMap__site">
          <span class="routeMap__site-pulse" aria-hidden="true"/>
          <span class="routeMap__site-label">{{ currentSite }}</span>
          <span class="routeMap__site-caption">آدرس سایت شما</span>
        </div>

        <div class="routeMap__columns" aria-hidden="true">
          <span>آدرس در مرورگر</span>
          <span class="routeMap__columns-arrow">→</span>
          <span>برنامه‌ای که باز می‌شود</span>
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
                  صفحه اصلی
                </span>
                <span v-if="route.is_lock" class="routeCard__badge routeCard__badge--lock">
                  <Icon :is="saxIcon.lock" size="xs"/>
                  <span>ثابت</span>
                </span>
              </div>

              <div class="routeCard__url">
                <span class="routeCard__url-origin">{{ currentSite }}</span>
                <span class="routeCard__url-path">{{ routeUrlSuffix(route.path) }}</span>
              </div>
            </div>

            <div class="routeCard__connector" aria-hidden="true">
              <Icon :is="saxIcon.arrowRight" class="routeCard__connector-arrow"/>
            </div>

            <button
                type="button"
                class="routeCard__app"
                :title="appActionLabel(route)"
                @click="openRouteEditor(route)"
            >
              <AppIcon v-bind="appIconProps(routeApp(route))" size="sm"/>
              <span class="routeCard__app-name">{{ appDisplayName(route) }}</span>
            </button>

            <div v-if="canManageRoute(route)" class="routeCard__actions">
              <button
                  type="button"
                  class="routeCard__action routeCard__action--edit"
                  title="ویرایش"
                  @click="editRoute(route)"
              >
                <Icon :is="saxIcon.edit" class="pageRoutes__action-icon" size="sm"/>
              </button>
              <button
                  v-if="!isDefaultRoute(route)"
                  type="button"
                  class="routeCard__action routeCard__action--delete"
                  title="حذف"
                  @click="deleteRoute(route.path)"
              >
                <Icon :is="saxIcon.remove" class="pageRoutes__action-icon" size="sm"/>
              </button>
            </div>
          </li>

          <li>
            <button type="button" class="routeCard routeCard--add" @click="openModalAddEditRoute()">
              <span class="routeCard__add-icon" aria-hidden="true">+</span>
              <span class="routeCard__add-label">افزودن آدرس جدید</span>
              <span class="routeCard__add-hint">{{ currentSite }}/shop</span>
            </button>
          </li>
        </ul>
      </div>

      <PageEmpty
          v-else
          title="هنوز آدرسی تعریف نشده"
          description="با دکمهٔ افزودن، اولین آدرس را ثبت کنید."
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
    `<p>اینجا مشخص می‌کنید با باز کردن هر آدرس، <strong>کدام برنامه</strong> برای بازدیدکننده نمایش داده شود.</p>` +
    `<h3>مثال ساده</h3>` +
    `<ul dir="ltr">` +
    `    <li><code>${currentSite}/</code> → برنامهٔ صفحه اصلی</li>` +
    `    <li><code>${currentSite}/manager</code> → پنل مدیریت</li>` +
    `    <li><code>${currentSite}/shop</code> → فروشگاه</li>` +
    `</ul>` +
    `<p>کافی است آدرس را بنویسید و برنامهٔ مورد نظر را انتخاب کنید.</p>`
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

function routeApp(route) {
  return appStore.fetchAppByPackage(route?.package);
}

function appDisplayName(route) {
  return routeApp(route)?.name ?? 'برنامه نامشخص';
}

function appActionLabel(route) {
  if (isDefaultRoute(route)) return 'تغییر برنامهٔ صفحه اصلی';
  if (route.is_lock) return appDisplayName(route);
  return `تغییر برنامه برای ${currentSite}${routeUrlSuffix(route.path)}`;
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

</script>

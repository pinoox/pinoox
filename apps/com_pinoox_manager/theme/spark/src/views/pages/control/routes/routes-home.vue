<template>
  <Page title="مسیریابی" class="pageRoutes">
    <template #toolbar>
      <Menu @click="openModalAddEditRoute()" :icon="saxIcon.add" label="افزودن"/>
      <Menu @click="openModal(ModalGuide, { message: guideMessage })" :icon="saxIcon.guide" label="راهنما"/>
    </template>

    <div v-if="routes.length" class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-800">
        <thead class="">
        <tr>
          <th class="px-6 py-3 text-right text-xs font-medium text-gray-200 uppercase tracking-wider">اپلیکیشن</th>
          <th class="px-6 py-3 text-right text-xs font-medium text-gray-200 uppercase tracking-wider">آدرس مسیر</th>
          <th class="px-6 py-3 text-right text-xs font-medium text-gray-200 uppercase tracking-wider">عملیات</th>
        </tr>
        </thead>
        <tbody class=" divide-gray-200">
        <tr v-for="route in routes" :key="route.id">
          <td class="px-4 whitespace-nowrap text-sm text-gray-200">
            <div class="flex items-center">
              <img :src="route.app.icon" :alt="route.app.name" class="w-8 h-8 mr-2"/>
              <span class="pr-4">{{ route.app.name }}</span>
            </div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 ">
            {{ currentSite }}/{{ route.path }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
            <button @click="editRoute(route)" class=" hover:text-blue-300 ml-4">
              <Icon :is="saxIcon.edit"></Icon>
            </button>
            <button @click="deleteRoute(route.id)" class="hover:text-red-700 ml-4">
              <Icon :is="saxIcon.remove"></Icon>
            </button>
          </td>
        </tr>
        </tbody>
      </table>
    </div>

    <PageEmpty
        v-else
        title="هیچ مسیری ثبت نشده است"
        description="برای افزودن مسیر جدید، روی دکمه افزودن کلیک کنید."
        :icon="saxIcon.routes"
    />
  </Page>
</template>

<script setup>
import {ref} from 'vue';
import {saxIcon} from '@/const/icons.js';
import {openModal} from '@kolirt/vue-modal';
import ModalGuide from '@views/components/commons/ModalGuide.vue';
import ModalAddEditRoute from '@views/pages/control/routes/modal-add-edit-route.vue';

const apps = ref([
  {id: 1, name: 'اتوماسیون داخلی', icon: new URL('@/assets/media/icons/1.png', import.meta.url).href},
  {id: 2, name: 'حسابداری شخصی', icon: new URL('@/assets/media/icons/2.png', import.meta.url).href},
]);

/*const routes = ref([
  {id: 1, app: apps.value[0], path: 'automation'},
  {id: 2, app: apps.value[1], path: 'accounting'},
]);*/
const routes = ref([]);

const currentSite = window.location.origin;

const guideMessage = ref(
    `<p>در <strong>پینوکس</strong> می‌توانید مسیرهایی را تعریف کنید تا هر مسیر، اپلیکیشن خاصی را نمایش دهد.</p>` +
    `<p>به عنوان مثال، اگر کاربر <code>${currentSite}/shop</code> را وارد کند، اپلیکیشن فروشگاه باز می‌شود.</p>` +
    `<h3>مثال مسیرها:</h3>` +
    `<ul>` +
    `    <li><code>${currentSite}/shop</code> → فروشگاه</li>` +
    `    <li><code>${currentSite}/blog</code> → وبلاگ</li>` +
    `</ul>` +
    `<p>با این روش، کاربران به‌صورت خودکار به اپلیکیشن‌های مرتبط هدایت می‌شوند.</p>`
);

function openModalAddEditRoute(route = null) {
  openModal(ModalAddEditRoute, {payload: route}).then((res) => {
  });
}

function editRoute(route) {
  openModalAddEditRoute(route);
}

function deleteRoute(routeId) {
  routes.value = routes.value.filter((route) => route.id !== routeId);
}
</script>
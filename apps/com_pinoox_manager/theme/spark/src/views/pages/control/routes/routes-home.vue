<template>
    <Page title="مسیریابی" class="pageRoutes">
        <template #toolbar>
            <Menu @click="openModalAddEditRoute()" :icon="saxIcon.add" label="افزودن"/>
            <Menu @click="openModal(ModalGuide, { message: guideMessage })" :icon="saxIcon.guide" label="راهنما"/>
        </template>

        <div v-if="routeFilters.length" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-800">
                <thead class="">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-200 uppercase tracking-wider">
                        اپلیکیشن
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-200 uppercase tracking-wider">آدرس
                        مسیر
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-200 uppercase tracking-wider">عملیات
                    </th>
                </tr>
                </thead>
                <tbody class=" divide-gray-200">
                <tr v-for="(route,index) in routeFilters" :key="index">
                    <td class="px-4 whitespace-nowrap text-sm text-gray-200">
                        <div class="flex items-center">
                            <img :src="route.icon" :alt="route.name" class="w-8 h-8 mr-2"/>
                            <span class="pr-4">{{ route.name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300 ltr">
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
import {computed, onMounted, ref} from 'vue';
import {saxIcon} from '@/const/icons.js';
import {openModal} from '@kolirt/vue-modal';
import ModalGuide from '@views/components/commons/ModalGuide.vue';
import ModalAddEditRoute from '@views/pages/control/routes/modal-add-edit-route.vue';
import {routerAPI} from "@api/router.js";

const routes = ref([]);
const routeFilters = computed(() => {
    return Object.values(routes.value);
});

const currentSite = PINOOX.URL.SITE;

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
        console.log(payload, res);
    });
}

function editRoute(route) {
    openModalAddEditRoute(route);
}

function deleteRoute(routeId) {
    routes.value = routes.value.filter((route) => route.id !== routeId);
}

const getAll = () => {
    routerAPI.getAll().then((response) => {
        routes.value = response.data;
    });
}

const add = () => {
    routerAPI.add()
}

onMounted(() => {
    getAll();
})
</script>
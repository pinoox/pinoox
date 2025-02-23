<template>
    <SimpleModal :title="title" size="sm" class="modalRoutes">
        <div v-if="currentStep === 1" class="form">
            <Input
                    type="text"
                    v-model="params.path"
                    label="آدرس مسیر"
                    direction="ltr"
                    placeholder="نام مسیر"
                    :prefix="domain + '/'"
            />
            <div class="flex justify-end mt-4">
                <Button @click="closeModal" label="بستن" variant="dark"/>
                <Button @click="goToNextStep" :disabled="!params.path" label="ادامه" variant="primary"/>
            </div>
        </div>

        <div v-else class="form">
            <Input
                    type="text"
                    v-model="searchQuery"
                    label="جستجو..."
                    placeholder="نام اپلیکیشن را بنویسید"
            />
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-6 mt-12">
                <div
                        v-for="app in filteredApps"
                        :key="app.package_name"
                        @click="selectPackage(app)"
                        class="flex flex-col items-center justify-center space-y-2 cursor-pointer transition-transform duration-300 ease-in-out hover:scale-110"
                        :class="{
            'opacity-100': app.package_name === params.packageName,
            'opacity-40': app.package_name !== params.packageName && params.packageName
          }"
                >
                    <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl flex items-center justify-center shadow-lg">
                        <img :src="app.icon" :alt="app.name" class="w-16 h-16 md:w-20 md:h-20"/>
                    </div>
                    <span class="text-sm text-gray-400">{{ app.name }}</span>
                </div>
            </div>
            <div class="flex justify-between mt-4">
                <Button v-if="!props.hasSelectApp" @click="goToPreviousStep" label="بازگشت" variant="dark"/>
                <Button v-else @click="closeModal" label="بستن" variant="dark"/>
                <Button @click="save" :disabled="!params.packageName" label="ذخیره" variant="primary"/>
            </div>
        </div>
    </SimpleModal>
</template>

<script setup>
import {ref, computed, watch} from 'vue';
import {closeModal} from '@kolirt/vue-modal';
import {useAppStore} from "@/stores/modules/app.js";
import {useRouteStore} from "@/stores/modules/route.js";
import {routerAPI} from "@api/router.js";

const props = defineProps({
    payload: {
        type: Object,
        default: null,
    },
    hasSelectApp: {
        type: Boolean,
        default: false,
    },
});

const appStore = useAppStore();
const routeStore = useRouteStore();

const domain = computed(()=> window.location.hostname);

const params = ref({
    path: '',
    packageName: null,
    oldPath: null,
});

const searchQuery = ref('');
const currentStep = ref(1);


const filteredApps = computed(() => {
    return appStore.fetchAppsLikeName(searchQuery.value);
});

const selectPackage = (app) => {

    if (app.package_name === params.value.packageName)
        params.value.packageName = null;
    else
        params.value.packageName = app.package_name;
};

const goToNextStep = () => {
    currentStep.value = 2;
};

const goToPreviousStep = () => {
    currentStep.value = 1;
};

watch(() => props.payload, (data) => {

    params.value = {
        path: data?.path,
        packageName: data?.package,
        oldPath: data?.path,
    }
}, {
    immediate: true,
});

watch(() => props.hasSelectApp, (status) => {
    currentStep.value = status ? 2 : 1;
}, {
    immediate: true,
});

const save = () => {
    routerAPI.save(params.value).then(() => {
        routeStore.saveRoute(params.value.path, params.value.packageName, params.value.oldPath);
        closeModal();
    })

};

const isEdit = computed(() => (!!props.payload));
const title = computed(() => (isEdit.value ? 'ویرایش' : 'افزودن مسیر جدید'));
</script>
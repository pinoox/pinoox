<template>
    <SimpleModal :title="title" size="sm" class="modalRoutes">
        <div v-if="!props.hasSelectApp" class="modalRoutes__steps" aria-hidden="true">
            <span class="modalRoutes__step" :class="{ 'is-active': currentStep === 1, 'is-done': currentStep > 1 }">۱. آدرس</span>
            <span class="modalRoutes__stepLine"/>
            <span class="modalRoutes__step" :class="{ 'is-active': currentStep === 2 }">۲. برنامه</span>
        </div>

        <div v-if="currentStep === 1" class="form">
            <p class="modalRoutes__hint">آدرسی را بنویسید که می‌خواهید در مرورگر باز شود.</p>
            <Input
                    type="text"
                    v-model="params.path"
                    label="آدرس"
                    direction="ltr"
                    placeholder="shop"
                    :prefix="addressPrefix"
            />
            <div class="flex justify-end mt-4 gap-2">
                <Button @click="closeModal" label="بستن" variant="dark"/>
                <Button @click="goToNextStep" :is-disabled="!canGoNext" label="انتخاب برنامه" variant="primary"/>
            </div>
        </div>

        <div v-else class="form">
            <p class="modalRoutes__hint">
                <span v-if="props.hasSelectApp">وقتی کسی آدرس اصلی سایت را باز می‌کند، کدام برنامه نمایش داده شود؟</span>
                <span v-else>با باز کردن <code>{{ routePreview }}</code> کدام برنامه نمایش داده شود؟</span>
            </p>
            <Input
                    type="text"
                    v-model="searchQuery"
                    label="جستجو"
                    placeholder="نام برنامه را بنویسید"
            />
            <p v-if="!filteredApps.length" class="modalRoutes__emptyApps">برنامه‌ای برای انتخاب پیدا نشد.</p>
            <div v-else class="modal-app-picker grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-6 mt-8">
                <button
                        type="button"
                        v-for="app in filteredApps"
                        :key="app.package_name"
                        @click="selectPackage(app)"
                        class="modalRoutes__appOption"
                        :class="{
            'is-selected': app.package_name === params.packageName,
            'is-dimmed': appOptionDimmed(app),
            'is-disabled': !isAppSelectable(app),
          }"
                        :disabled="!isAppSelectable(app)"
                        :title="appOptionTitle(app)"
                >
                    <AppBrandIcon
                        v-if="isManagerBrandApp(app)"
                        v-bind="managerBrandIconProps(app)"
                        size="lg"
                    />
                    <AppIcon v-else v-bind="resolveRouteAppIconProps(app)" size="lg"/>
                    <span class="text-sm text-gray-400">{{ resolveAppDisplayLabel(app) }}</span>
                </button>
            </div>
            <div class="flex justify-between mt-4 gap-2">
                <Button v-if="!props.hasSelectApp" @click="goToPreviousStep" label="بازگشت" variant="dark" :is-disabled="isSaving"/>
                <Button v-else @click="closeModal" label="بستن" variant="dark" :is-disabled="isSaving"/>
                <Button
                    type="button"
                    :is-disabled="!canSave || isSaving"
                    :is-loading="isSaving"
                    label="ذخیره"
                    variant="primary"
                    @click="save"
                />
            </div>
        </div>
    </SimpleModal>
</template>

<script setup>
defineOptions({modalGroup: 'default'});

import {ref, computed, watch, onMounted, nextTick} from 'vue';
import {closeModal, useModalContext} from '@kolirt/vue-modal';
import Button from '@/views/components/widgets/Button.vue';
import {useAppStore} from "@/stores/modules/app.js";
import {useRouteStore} from "@/stores/modules/route.js";
import {resolveRouteAppIconProps} from "@utils/helpers/appIconProps.js";
import {resolveAppDisplayLabel, isManagerBrandApp, managerBrandIconProps} from "@utils/helpers/appDisplayLabel.js";
import {canAssignAnotherRoute, resolveRouterMode} from "@utils/helpers/appRoutePolicy.js";
import {translate} from "@utils/helpers/managerLang.js";
import {routerAPI} from "@api/router.js";
import {unwrapResponse} from "@utils/helpers/apiHelper.js";
import {resolveApiFailure} from "@utils/apiEnvelope.js";
import {toastError} from "@utils/helpers/toastHelper.js";
import { getUrl } from '@/boot.js';
import {formatSiteOriginForDisplay, formatSiteOriginPrefix} from '@utils/helpers/siteUrlHelper.js';

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

const {confirm} = useModalContext();
const appStore = useAppStore();
const routeStore = useRouteStore();

const siteUrl = getUrl().SITE;

const addressPrefix = computed(() => formatSiteOriginPrefix(siteUrl));

const params = ref({
    path: '',
    packageName: null,
    oldPath: '',
});

const searchQuery = ref('');
const currentStep = ref(1);
const isSaving = ref(false);

const isEditingRoute = computed(() => Boolean(props.payload?.path) && !props.hasSelectApp);

const filteredApps = computed(() => {
    const routes = routeStore.routeList;
    const editingPackage = props.payload?.package ?? null;

    return appStore.fetchAppsLikeName(searchQuery.value).filter((app) => {
        if (isEditingRoute.value && app.package_name === editingPackage) {
            return true;
        }

        return canAssignAnotherRoute(app, routes);
    });
});

function isAppSelectable(app) {
    if (isEditingRoute.value && app.package_name === (props.payload?.package ?? params.value.packageName)) {
        return true;
    }

    return canAssignAnotherRoute(app, routeStore.routeList);
}

function appOptionDimmed(app) {
    if (app.package_name === params.value.packageName) {
        return false;
    }

    if (params.value.packageName) {
        return true;
    }

    return !isAppSelectable(app);
}

function appOptionTitle(app) {
    if (isAppSelectable(app)) {
        return resolveAppDisplayLabel(app);
    }

    if (resolveRouterMode(app) === 'single') {
        return translate('app_single_route_only');
    }

    return resolveAppDisplayLabel(app);
}

const canGoNext = computed(() => {
    return String(params.value.path ?? '').trim().length > 0;
});

const canSave = computed(() => {
    return Boolean(params.value.packageName) && (props.hasSelectApp || canGoNext.value);
});

const routePreview = computed(() => {
    const path = String(params.value.path ?? '').trim();
    const base = formatSiteOriginForDisplay(siteUrl);
    if (!path || path === '/') return `${base}/`;
    const normalized = path.startsWith('/') ? path : `/${path}`;
    return `${base}${normalized}`;
});

const selectPackage = (app) => {
    if (!isAppSelectable(app)) {
        toastError(translate('app_single_route_only'));
        return;
    }

    params.value.packageName = app.package_name;
};

const goToNextStep = () => {
    if (!canGoNext.value) {
        toastError('لطفاً آدرس را وارد کنید.');
        return;
    }
    currentStep.value = 2;
};

const goToPreviousStep = () => {
    currentStep.value = 1;
};

const resetForm = (data = null) => {
    params.value = {
        path: data?.path ?? '',
        packageName: data?.package ?? null,
        oldPath: data?.path ?? '',
    };
    searchQuery.value = '';
    isSaving.value = false;
    currentStep.value = props.hasSelectApp ? 2 : 1;
};

watch(
    () => [props.payload, props.hasSelectApp],
    ([data]) => {
        resetForm(data);
    },
    {immediate: true},
);

onMounted(async () => {
    if (!appStore.isLoaded) {
        await appStore.getApps();
    }
});

function normalizePath(rawPath) {
    const value = String(rawPath ?? '').trim();
    if (!value || value === '/') {
        return '/';
    }
    return value.startsWith('/') ? value : `/${value}`;
}

function buildSavePayload() {
    const path = normalizePath(params.value.path);
    const packageName = params.value.packageName;

    if (!props.hasSelectApp && !String(params.value.path ?? '').trim()) {
        throw new Error('لطفاً آدرس را وارد کنید.');
    }

    if (!packageName) {
        throw new Error('لطفاً یک برنامه انتخاب کنید.');
    }

    return {
        path,
        packageName,
        oldPath: params.value.oldPath ?? '',
    };
}

function resolveErrorMessage(error) {
    return resolveApiFailure(error);
}

const save = async () => {
    if (isSaving.value) {
        return;
    }

    let payload;

    try {
        payload = buildSavePayload();
    } catch (validationError) {
        toastError(validationError.message);
        return;
    }

    isSaving.value = true;
    await nextTick();

    try {
        const response = await routerAPI.save(payload);
        unwrapResponse(response);
        routeStore.saveRoute(payload.path, payload.packageName, payload.oldPath || null);
        confirm();
    } catch (error) {
        toastError(resolveErrorMessage(error));
    } finally {
        isSaving.value = false;
    }
};

const isEdit = computed(() => (!!props.payload));
const title = computed(() => {
    if (props.hasSelectApp) return 'برنامهٔ صفحه اصلی';
    return isEdit.value ? 'ویرایش آدرس' : 'افزودن آدرس جدید';
});
</script>

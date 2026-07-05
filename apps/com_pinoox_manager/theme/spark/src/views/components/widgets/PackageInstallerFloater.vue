<template>
  <Teleport to="body">
    <button
        v-if="store.minimized"
        type="button"
        class="packageInstallerFab"
        :class="{ 'is-busy': store.isBusy }"
        aria-label="نصب‌کننده بسته"
        @click="restorePanel"
    >
      <span class="packageInstallerFab__icon">
        <AppIcon v-if="store.meta" v-bind="packageIconProps" size="sm" variant="soft"/>
        <Icon v-else :is="saxIcon.upload" size="sm"/>
      </span>
      <span class="packageInstallerFab__text">
        <span v-if="store.isBusy" class="packageInstallerFab__progress">{{ store.progress }}%</span>
        <span v-else class="packageInstallerFab__label">نصب بسته</span>
      </span>
    </button>

    <div
        v-if="store.visible && !store.minimized"
        class="packageInstallerBackdrop"
        aria-hidden="true"
    />

    <DraggableWidget
        v-if="store.visible && !store.minimized"
        class="packageInstaller"
        :class="{ 'is-advanced-open': store.showAdvanced && store.phase === 'preview' }"
        centered
    >
      <template #header>
        <div class="packageInstaller__head">
          <div class="packageInstaller__title-wrap">
            <Icon :is="saxIcon.upload" size="sm"/>
            <span class="packageInstaller__title">نصب‌کننده بسته</span>
          </div>
          <div class="packageInstaller__actions">
            <button
                v-if="store.isBusy"
                type="button"
                class="packageInstaller__iconBtn"
                aria-label="کوچک‌کردن"
                @click="store.minimized = true"
            >
              <span aria-hidden="true">−</span>
            </button>
            <button
                type="button"
                class="packageInstaller__iconBtn"
                aria-label="بستن"
                @click="store.dismiss()"
            >
              <span aria-hidden="true">×</span>
            </button>
          </div>
        </div>
      </template>

      <div class="packageInstaller__body">
        <template v-if="store.phase === 'idle'">
          <FileUploader ref="fileUploaderRef" compact @select="onSelect"/>
          <div class="packageInstaller__foot">
            <Button
                label="بارگذاری و بررسی"
                variant="primary"
                :disabled="!selectedFile"
                @click="startUpload"
            />
          </div>
        </template>

        <template v-else-if="store.phase === 'loading'">
          <div class="packageInstaller__loading">
            <WidgetLoading/>
            <p class="packageInstaller__statusTitle">{{ store.loadingMessage }}</p>
            <p class="packageInstaller__waitHint">چند لحظه صبر کنید…</p>
          </div>
        </template>

        <template v-else-if="store.phase === 'uploading'">
          <p class="packageInstaller__statusTitle">در حال بارگذاری بسته…</p>
          <p class="packageInstaller__waitHint">تا اتمام بارگذاری بسته صبر کنید و صفحه را نبندید.</p>
          <div class="packageInstaller__progressTrack">
            <div class="packageInstaller__progressBar" :style="{ width: `${store.progress}%` }"/>
          </div>
          <p class="packageInstaller__statusMeta">{{ store.progress }}%</p>
        </template>

        <template v-else-if="store.phase === 'preview' && store.meta">
          <div class="packageInstaller__scroll">
            <div class="packageInstaller__card">
              <AppIcon v-bind="packageIconProps" size="lg" class="packageInstaller__cardIcon"/>
              <h3 class="packageInstaller__packageName">{{ displayName }}</h3>
              <p v-if="store.meta.description" class="packageInstaller__description">{{ store.meta.description }}</p>
              <div v-if="hasPackageFacts" class="packageInstaller__facts">
                <div v-if="displayVersion" class="packageInstaller__fact">
                  <span class="packageInstaller__factLabel">ورژن</span>
                  <span class="packageInstaller__factValue" dir="ltr">{{ displayVersion }}</span>
                </div>
                <div v-if="store.meta.size" class="packageInstaller__fact">
                  <span class="packageInstaller__factLabel">حجم</span>
                  <span class="packageInstaller__factValue" dir="ltr">{{ store.meta.size }}</span>
                </div>
              </div>
            </div>

            <div v-if="!store.canInstall" class="packageInstaller__incompatible">
              <Icon :is="saxIcon.notifyInfo" size="sm"/>
              <span>{{ incompatibilityMessage }}</span>
            </div>

            <button
                v-if="store.meta.type === 'app'"
                type="button"
                class="packageInstaller__advancedToggle"
                @click="toggleAdvanced"
            >
              <span>{{ store.showAdvanced ? 'بستن تنظیمات پیشرفته' : 'تنظیمات پیشرفته' }}</span>
              <span class="packageInstaller__advancedChevron" :class="{ 'is-open': store.showAdvanced }">›</span>
            </button>

            <div v-if="store.showAdvanced && store.meta.type === 'app'" class="packageInstaller__advanced">
              <Input
                  v-model="store.database.prefix"
                  label="پیشوند جداول"
                  direction="ltr"
                  placeholder="app_"
                  :disabled="store.prefixLoading"
                  @update:modelValue="onPrefixInput"
                  @blur="onPrefixBlur"
              />
              <p v-if="prefixHint || store.prefixLoading" class="packageInstaller__prefixStatus" :class="prefixHintClass">
                <span v-if="store.prefixLoading">در حال بررسی پیشوند…</span>
                <span v-else>{{ prefixHint }}</span>
              </p>

              <label class="packageInstaller__customDbToggle">
                <input v-model="store.useCustomDatabase" type="checkbox"/>
                <span>استفاده از دیتابیس جدا برای این اپ</span>
              </label>

              <template v-if="store.useCustomDatabase">
                <DarkSelect
                    v-model="store.database.connection"
                    label="درایور دیتابیس"
                    :options="databaseDriverOptions"
                />
                <div class="packageInstaller__advancedGrid">
                  <Input v-model="store.database.host" label="میزبان" direction="ltr"/>
                  <Input v-model="store.database.port" label="پورت" direction="ltr"/>
                  <Input v-model="store.database.database" label="نام دیتابیس" direction="ltr"/>
                  <Input v-model="store.database.username" label="نام کاربری" direction="ltr"/>
                  <Input v-model="store.database.password" label="رمز عبور" type="password" direction="ltr"/>
                </div>
                <div class="packageInstaller__advancedActions">
                  <Button
                      label="تست اتصال"
                      variant="primary"
                      outline
                      size="sm"
                      full-width
                      :is-loading="store.connectionTesting"
                      @click="testDatabaseConnection"
                  />
                </div>
              </template>
            </div>
          </div>

          <div class="packageInstaller__foot">
            <Button label="لغو" variant="dark" @click="store.reset()"/>
            <Button
                :label="installButtonLabel"
                variant="primary"
                :disabled="!store.canInstall"
                :is-loading="store.connectionTesting"
                @click="confirmInstall"
            />
          </div>
        </template>

        <template v-else-if="store.phase === 'installing'">
          <div v-if="store.meta" class="packageInstaller__statusHead">
            <AppIcon v-bind="packageIconProps" size="sm"/>
            <p class="packageInstaller__statusTitle">{{ store.actionLabel }}…</p>
          </div>
          <p v-else class="packageInstaller__statusTitle">{{ store.actionLabel }}…</p>
          <p class="packageInstaller__waitHint">تا پایان نصب صبر کنید و پنجره را نبندید.</p>
          <div class="packageInstaller__progressTrack">
            <div class="packageInstaller__progressBar" :style="{ width: `${store.progress}%` }"/>
          </div>
          <p class="packageInstaller__statusMeta">{{ store.progress }}%</p>

          <div v-if="displaySteps.length" class="packageInstaller__steps">
            <p class="packageInstaller__stepsTitle">مراحل نصب</p>
            <ul>
              <li
                  v-for="(step, index) in displaySteps"
                  :key="`${step.step}-${index}`"
                  class="packageInstaller__step"
                  :class="`is-${step.status}`"
              >
                <span class="packageInstaller__stepIcon">{{ stepIcon(step.status) }}</span>
                <span class="packageInstaller__stepText">
                  <strong>{{ installStepLabel(step.step) }}</strong>
                  <small>{{ step.message }}</small>
                </span>
              </li>
            </ul>
          </div>
          <WidgetLoading v-else/>
        </template>

        <template v-else-if="store.phase === 'route'">
          <p class="packageInstaller__success">نصب با موفقیت انجام شد.</p>
          <div class="packageInstaller__routePrompt">
            <h3>مسیریابی اپلیکیشن</h3>
            <p>این اپلیکیشن قابل مسیریابی است. می‌خواهید الان یک آدرس به آن اختصاص دهید؟</p>
            <Input
                v-model="store.routePrompt.path"
                label="آدرس در مرورگر"
                direction="ltr"
                placeholder="shop"
                prefix="/"
            />
          </div>
          <div class="packageInstaller__foot">
            <Button label="بعداً" variant="dark" @click="skipRoutePrompt"/>
            <Button label="تخصیص آدرس" variant="primary" @click="assignRoute"/>
          </div>
        </template>

        <template v-else-if="store.phase === 'success'">
          <div v-if="store.meta" class="packageInstaller__successHead">
            <AppIcon v-bind="packageIconProps" size="sm"/>
            <p class="packageInstaller__success">{{ store.actionLabel }} با موفقیت انجام شد.</p>
          </div>
          <p v-else class="packageInstaller__success">{{ store.actionLabel }} با موفقیت انجام شد.</p>
          <div class="packageInstaller__foot">
            <Button label="بستن" variant="primary" @click="store.dismiss()"/>
            <Button label="بسته دیگر" variant="dark" outline @click="store.reset()"/>
          </div>
        </template>

        <template v-else-if="store.phase === 'error'">
          <p class="packageInstaller__error">{{ store.error }}</p>
          <div v-if="store.steps.length" class="packageInstaller__steps is-compact">
            <ul>
              <li
                  v-for="(step, index) in store.steps"
                  :key="`${step.step}-${index}`"
                  class="packageInstaller__step"
                  :class="`is-${step.status}`"
              >
                <span class="packageInstaller__stepIcon">{{ stepIcon(step.status) }}</span>
                <span class="packageInstaller__stepText">
                  <strong>{{ installStepLabel(step.step) }}</strong>
                </span>
              </li>
            </ul>
          </div>
          <div class="packageInstaller__foot">
            <Button label="تلاش مجدد" variant="primary" @click="store.reset()"/>
            <Button label="بستن" variant="dark" @click="store.dismiss()"/>
          </div>
        </template>
      </div>
    </DraggableWidget>
  </Teleport>
</template>

<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import DraggableWidget from '@/views/components/widgets/DraggableWidget.vue';
import FileUploader from '@/views/components/widgets/FileUploader.vue';
import WidgetLoading from '@/views/components/desktop-widgets/WidgetLoading.vue';
import AppIcon from '@/views/components/widgets/AppIcon.vue';
import {saxIcon} from '@/const/icons.js';
import {packageMetaIconProps} from '@utils/helpers/appIconProps.js';
import {usePackageInstallerStore} from '@/stores/modules/packageInstaller.js';
import {usePackageInstaller} from '@/views/composables/usePackageInstaller.js';
import {useAppStore} from '@/stores/modules/app.js';
import DarkSelect from '@/views/components/form/DarkSelect.vue';

const databaseDriverOptions = [
  {value: 'mysql', label: 'MySQL'},
  {value: 'pgsql', label: 'PostgreSQL'},
  {value: 'sqlite', label: 'SQLite'},
  {value: 'sqlsrv', label: 'SQL Server'},
];

const store = usePackageInstallerStore();
const appStore = useAppStore();
const {
    uploadSelectedFile,
    confirmInstall,
    consumePendingFile,
    onPrefixInput,
    onPrefixBlur,
    testDatabaseConnection,
    assignRoute,
    skipRoutePrompt,
    installStepLabel,
    toggleAdvanced,
} = usePackageInstaller();

const fileUploaderRef = ref(null);
const selectedFile = ref(null);

const displayName = computed(() => {
    if (!store.meta) {
        return '';
    }

    return store.meta.name || store.meta.template_name || store.meta.package_name || store.filename;
});

const displayVersion = computed(() => {
    if (!store.meta?.version) {
        return '';
    }

    return String(store.meta.version);
});

const hasPackageFacts = computed(() => {
    return Boolean(displayVersion.value || store.meta?.size);
});

const packageIconProps = computed(() => packageMetaIconProps(store.meta, appStore));

const incompatibilityMessage = computed(() => {
    const issues = store.meta?.compatibility?.issues ?? [];

    if (issues.length) {
        return issues[0];
    }

    return 'این بسته با نسخه فعلی پینوکس سازگار نیست.';
});

const installButtonLabel = computed(() => {
    if (!store.canInstall) {
        return 'غیرقابل نصب';
    }

    if (store.meta?.install_mode === 'update') {
        return store.meta?.type === 'theme' ? 'بروزرسانی' : 'بروزرسانی';
    }

    return 'نصب';
});

const displaySteps = computed(() => store.steps);

const prefixHint = computed(() => {
    if (store.prefixLoading) {
        return '';
    }

    const status = store.prefixStatus;

    if (!status || status.available) {
        return 'پیشوند برای استفاده مناسب است.';
    }

    if (status.error) {
        return status.error;
    }

    if (status.tables_exist) {
        return 'جداولی با این پیشوند در دیتابیس وجود دارد.';
    }

    return 'پیشوند برای استفاده مناسب است.';
});

const prefixHintClass = computed(() => {
    if (store.prefixLoading) {
        return '';
    }

    const status = store.prefixStatus;

    if (!status || status.available) {
        return 'is-ok';
    }

    if (status.error || status.tables_exist) {
        return 'is-warn';
    }

    return 'is-ok';
});

function stepIcon(status) {
    if (status === 'ok') {
        return '✓';
    }

    if (status === 'error') {
        return '×';
    }

    if (status === 'skipped') {
        return '–';
    }

    return '…';
}

function onSelect(file) {
    selectedFile.value = file;
}

function startUpload() {
    if (!selectedFile.value) {
        return;
    }

    uploadSelectedFile(selectedFile.value);
}

function restorePanel() {
    store.minimized = false;
    store.visible = true;
}

watch(() => store.phase, (phase) => {
    if (phase === 'idle') {
        selectedFile.value = null;
        fileUploaderRef.value?.resetFile();
    }
});

onMounted(() => {
    consumePendingFile();
});

watch(() => store.pendingFile, () => {
    consumePendingFile();
});
</script>

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

        <template v-else-if="store.phase === 'uploading'">
          <p class="packageInstaller__statusTitle">در حال بارگذاری بسته…</p>
          <p class="packageInstaller__waitHint">تا اتمام بارگذاری بسته صبر کنید و صفحه را نبندید.</p>
          <div class="packageInstaller__progressTrack">
            <div class="packageInstaller__progressBar" :style="{ width: `${store.progress}%` }"/>
          </div>
          <p class="packageInstaller__statusMeta">{{ store.progress }}%</p>
        </template>

        <template v-else-if="store.phase === 'preview' && store.meta">
          <div class="packageInstaller__preview">
            <AppIcon v-bind="packageIconProps" size="md"/>
            <div class="packageInstaller__previewText">
              <div class="packageInstaller__badges">
                <span class="packageInstaller__badge">{{ store.typeLabel }}</span>
                <span class="packageInstaller__badge is-accent">{{ store.actionLabel }}</span>
              </div>
              <h3 class="packageInstaller__packageName">{{ displayName }}</h3>
              <p v-if="store.meta.description" class="packageInstaller__description">{{ store.meta.description }}</p>
            </div>
          </div>

          <div v-if="compatibilityIssues.length" class="packageInstaller__alert is-error">
            <strong>مشکلات سازگاری</strong>
            <ul>
              <li v-for="issue in compatibilityIssues" :key="issue">{{ issue }}</li>
            </ul>
          </div>

          <div v-if="store.meta.compatibility" class="packageInstaller__compat">
            <div>
              <span>حداقل نسخه پینوکس</span>
              <strong dir="ltr">#{{ store.meta.compatibility.minpin }}</strong>
              <span
                  class="packageInstaller__compatState"
                  :class="store.meta.compatibility.minpin_ok ? 'is-ok' : 'is-bad'"
              >
                {{ store.meta.compatibility.minpin_ok ? 'سازگار' : 'ناسازگار' }}
              </span>
            </div>
            <div v-if="store.meta.compatibility.kernel_version">
              <span>نسخه فعلی هسته</span>
              <strong dir="ltr">
                {{ store.meta.compatibility.kernel_version.name || '—' }}
                #{{ store.meta.compatibility.kernel_version.code ?? '—' }}
              </strong>
            </div>
          </div>

          <dl class="packageInstaller__meta">
            <div v-if="store.meta.type === 'app'">
              <dt>پکیج</dt>
              <dd dir="ltr">{{ store.meta.package_name }}</dd>
            </div>
            <div v-else>
              <dt>اپ میزبان</dt>
              <dd dir="ltr">{{ store.meta.app }}</dd>
              <dt>نام قالب</dt>
              <dd dir="ltr">{{ store.meta.name }}</dd>
            </div>
            <div>
              <dt>نسخه</dt>
              <dd>{{ store.meta.version }} <span dir="ltr">#{{ store.meta['version-code'] }}</span></dd>
            </div>
            <div v-if="store.meta.size">
              <dt>حجم</dt>
              <dd>{{ store.meta.size }}</dd>
            </div>
            <div v-if="store.meta.developer">
              <dt>توسعه‌دهنده</dt>
              <dd>{{ store.meta.developer }}</dd>
            </div>
            <div v-if="store.meta.database?.resolved_prefix">
              <dt>پیشوند جداول</dt>
              <dd dir="ltr">{{ store.meta.database.resolved_prefix }}</dd>
            </div>
          </dl>

          <button
              v-if="store.meta.type === 'app'"
              type="button"
              class="packageInstaller__advancedToggle"
              @click="store.showAdvanced = !store.showAdvanced"
          >
            {{ store.showAdvanced ? 'بستن تنظیمات پیشرفته' : 'تنظیمات پیشرفته' }}
          </button>

          <div v-if="store.showAdvanced && store.meta.type === 'app'" class="packageInstaller__advanced">
            <p class="packageInstaller__advancedHint">اتصال دیتابیس و پیشوند جداول را در صورت نیاز تغییر دهید.</p>
            <div class="packageInstaller__advancedGrid">
              <Input v-model="store.database.host" label="میزبان" direction="ltr"/>
              <Input v-model="store.database.port" label="پورت" direction="ltr"/>
              <Input v-model="store.database.database" label="نام دیتابیس" direction="ltr"/>
              <Input v-model="store.database.username" label="نام کاربری" direction="ltr"/>
              <Input v-model="store.database.password" label="رمز عبور" type="password" direction="ltr"/>
              <Input
                  v-model="store.database.prefix"
                  label="پیشوند جداول"
                  direction="ltr"
                  @blur="checkPrefix"
              />
            </div>
            <p class="packageInstaller__prefixHint">پیشوند یکتا برای جداول این اپ. اگر خالی یا تکراری باشد، خودکار اصلاح می‌شود.</p>
            <p v-if="prefixHint" class="packageInstaller__prefixStatus" :class="prefixHintClass">{{ prefixHint }}</p>
            <div class="packageInstaller__advancedActions">
              <Button label="بررسی پیشوند" variant="dark" outline size="sm" @click="checkPrefix"/>
              <Button label="تست اتصال" variant="dark" outline size="sm" @click="testDatabaseConnection"/>
            </div>
          </div>

          <div class="packageInstaller__foot">
            <Button label="لغو" variant="dark" @click="store.reset()"/>
            <Button
                :label="store.actionLabel"
                variant="primary"
                :disabled="!store.canInstall"
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

const store = usePackageInstallerStore();
const appStore = useAppStore();
const {
    uploadSelectedFile,
    confirmInstall,
    consumePendingFile,
    checkPrefix,
    testDatabaseConnection,
    assignRoute,
    skipRoutePrompt,
    installStepLabel,
} = usePackageInstaller();

const fileUploaderRef = ref(null);
const selectedFile = ref(null);

const displayName = computed(() => {
    if (!store.meta) {
        return '';
    }

    return store.meta.name || store.meta.template_name || store.meta.package_name || store.filename;
});

const packageIconProps = computed(() => packageMetaIconProps(store.meta, appStore));

const compatibilityIssues = computed(() => store.meta?.compatibility?.issues ?? []);

const displaySteps = computed(() => store.steps);

const prefixHint = computed(() => {
    const status = store.prefixStatus;

    if (!status) {
        return '';
    }

    if (status.error) {
        return status.error;
    }

    if (status.auto_adjusted) {
        return `پیشوند به ${status.resolved_prefix} تغییر یافت تا تداخل نداشته باشد.`;
    }

    if (status.tables_exist) {
        return 'جداولی با این پیشوند در دیتابیس وجود دارد.';
    }

    return 'پیشوند برای استفاده مناسب است.';
});

const prefixHintClass = computed(() => {
    const status = store.prefixStatus;

    if (!status) {
        return '';
    }

    if (status.error || status.tables_exist) {
        return 'is-warn';
    }

    if (status.auto_adjusted) {
        return 'is-info';
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

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
      <Icon :is="saxIcon.upload" size="sm"/>
      <span v-if="store.isBusy" class="packageInstallerFab__progress">{{ store.progress }}%</span>
      <span v-else class="packageInstallerFab__label">بسته</span>
    </button>

    <DraggableWidget
        v-else-if="store.visible"
        class="packageInstaller"
        initialX="calc(100% - 380px)"
        initialY="calc(100% - 420px)"
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
          <p class="packageInstaller__hint">
            فایل <strong>.pinx</strong> را انتخاب کنید. برای فایل‌های بزرگ‌تر از ۸ مگابایت، آپلود قطعه‌ای
            <strong>Pinion</strong> به‌صورت خودکار انجام می‌شود.
          </p>
          <FileUploader ref="fileUploaderRef" @select="onSelect"/>
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
          <p class="packageInstaller__statusTitle">در حال بارگذاری…</p>
          <div class="packageInstaller__progressTrack">
            <div class="packageInstaller__progressBar" :style="{ width: `${store.progress}%` }"/>
          </div>
          <p class="packageInstaller__statusMeta">{{ store.progress }}%</p>
        </template>

        <template v-else-if="store.phase === 'preview' && store.meta">
          <div class="packageInstaller__badges">
            <span class="packageInstaller__badge">{{ store.typeLabel }}</span>
            <span class="packageInstaller__badge is-accent">{{ store.actionLabel }}</span>
          </div>
          <h3 class="packageInstaller__packageName">{{ displayName }}</h3>
          <p v-if="store.meta.description" class="packageInstaller__description">{{ store.meta.description }}</p>
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
          </dl>
          <div class="packageInstaller__foot">
            <Button label="لغو" variant="dark" @click="store.reset()"/>
            <Button :label="store.actionLabel" variant="primary" @click="confirmInstall"/>
          </div>
        </template>

        <template v-else-if="store.phase === 'installing'">
          <p class="packageInstaller__statusTitle">{{ store.actionLabel }}…</p>
          <WidgetLoading/>
        </template>

        <template v-else-if="store.phase === 'success'">
          <p class="packageInstaller__success">{{ store.actionLabel }} با موفقیت انجام شد.</p>
          <div class="packageInstaller__foot">
            <Button label="بستن" variant="primary" @click="store.dismiss()"/>
            <Button label="بسته دیگر" variant="dark" outline @click="store.reset()"/>
          </div>
        </template>

        <template v-else-if="store.phase === 'error'">
          <p class="packageInstaller__error">{{ store.error }}</p>
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
import {saxIcon} from '@/const/icons.js';
import {usePackageInstallerStore} from '@/stores/modules/packageInstaller.js';
import {usePackageInstaller} from '@/views/composables/usePackageInstaller.js';

const store = usePackageInstallerStore();
const {uploadSelectedFile, confirmInstall, consumePendingFile} = usePackageInstaller();

const fileUploaderRef = ref(null);
const selectedFile = ref(null);

const displayName = computed(() => {
    if (!store.meta) {
        return '';
    }

    return store.meta.name || store.meta.template_name || store.meta.package_name || store.filename;
});

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

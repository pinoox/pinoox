import {watch} from 'vue';
import {appAPI} from '@api/app.js';
import {resolveUploadedFilename, uploadPackageFile} from '@utils/pinion.js';
import {usePackageInstallerStore} from '@/stores/modules/packageInstaller.js';
import {useAppStore} from '@/stores/modules/app.js';
import {toast} from '@global';

function unwrapPayload(response) {
    return response?.data?.data ?? response?.data ?? null;
}

function errorMessage(error) {
    return error?.response?.data?.message
        ?? error?.response?.data?.error
        ?? error?.message
        ?? 'خطا در پردازش بسته';
}

export function usePackageInstaller() {
    const store = usePackageInstallerStore();
    const appStore = useAppStore();

    async function uploadSelectedFile(file) {
        if (!file || !String(file.name).toLowerCase().endsWith('.pinx')) {
            store.setError('فقط فایل با پسوند .pinx مجاز است.');
            return;
        }

        store.setPhase('uploading');
        store.progress = 0;
        store.error = null;
        store.meta = null;
        store.filename = null;

        try {
            const uploadResult = await uploadPackageFile(file, {
                onProgress: (value) => {
                    store.setProgress(value);
                },
            });

            const filename = resolveUploadedFilename(uploadResult, file);

            if (!filename) {
                throw new Error('نام فایل بسته مشخص نشد.');
            }

            const metaResponse = await appAPI.packageMeta(filename);
            const meta = unwrapPayload(metaResponse);

            if (!meta) {
                throw new Error('خواندن اطلاعات بسته ممکن نشد.');
            }

            store.setMeta(meta, filename);
            store.setPhase('preview');
        } catch (error) {
            store.setError(errorMessage(error));
        }
    }

    async function confirmInstall() {
        if (!store.filename) {
            return;
        }

        store.setPhase('installing');
        store.error = null;

        try {
            await appAPI.installPackage(store.filename);
            await appStore.getApps();
            store.setPhase('success');
            toast({
                title: store.actionLabel + ' با موفقیت انجام شد',
                type: 'success',
            });
        } catch (error) {
            store.setError(errorMessage(error));
        }
    }

    function openInstaller(file = null) {
        if (file) {
            store.showWithFile(file);
            return;
        }

        store.show();
    }

    function consumePendingFile() {
        const file = store.pendingFile;

        if (!file) {
            return;
        }

        store.pendingFile = null;
        uploadSelectedFile(file);
    }

    watch(() => store.pendingFile, (file) => {
        if (file && store.visible && store.phase === 'idle') {
            consumePendingFile();
        }
    });

    async function previewStagedFile(filename) {
        store.show();
        store.error = null;

        try {
            const metaResponse = await appAPI.packageMeta(filename);
            const meta = unwrapPayload(metaResponse);

            if (!meta) {
                throw new Error('خواندن اطلاعات بسته ممکن نشد.');
            }

            store.setMeta(meta, filename);
            store.setPhase('preview');
        } catch (error) {
            store.setError(errorMessage(error));
        }
    }

    return {
        store,
        openInstaller,
        uploadSelectedFile,
        previewStagedFile,
        confirmInstall,
        consumePendingFile,
    };
}

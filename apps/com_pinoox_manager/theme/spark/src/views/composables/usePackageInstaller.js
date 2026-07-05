import {watch} from 'vue';
import {appAPI} from '@api/app.js';
import {routerAPI} from '@api/router.js';
import {resolveUploadedFilename, uploadPackageFile} from '@utils/pinion.js';
import {installStepLabel, sleep} from '@utils/packageInstall.js';
import {readApiErrorMessage} from '@utils/apiEnvelope.js';
import {usePackageInstallerStore} from '@/stores/modules/packageInstaller.js';
import {useAppStore} from '@/stores/modules/app.js';
import {toast} from '@global';

function unwrapPayload(response) {
    return response?.data?.data ?? response?.data ?? null;
}

function errorMessage(error) {
    return readApiErrorMessage(error) || 'خطا در پردازش بسته';
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

            store.setProgress(100);

            const filename = resolveUploadedFilename(uploadResult, file);

            if (!filename) {
                throw new Error('نام فایل بسته مشخص نشد.');
            }

            store.setLoading('در حال بررسی اطلاعات بسته…');

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

    async function toggleAdvanced() {
        store.showAdvanced = !store.showAdvanced;
    }

    function buildInstallPayload() {
        const payload = {
            filename: store.filename,
        };

        if (store.meta?.type === 'app' && (store.showAdvanced || store.useCustomDatabase)) {
            const database = {
                prefix: store.database.prefix,
            };

            if (store.useCustomDatabase) {
                Object.assign(database, {
                    connection: store.database.connection,
                    host: store.database.host,
                    port: store.database.port,
                    database: store.database.database,
                    username: store.database.username,
                    password: store.database.password,
                });
            }

            payload.database = database;
        }

        return payload;
    }

    async function pollInstallStatus(installId) {
        while (true) {
            const response = await appAPI.installPackageStatus(installId);
            const session = unwrapPayload(response) ?? response?.data ?? null;

            if (!session) {
                throw new Error('وضعیت نصب در دسترس نیست.');
            }

            store.setProgress(session.progress ?? 0);
            store.setSteps(session.steps ?? []);

            if (session.status === 'done' || session.status === 'failed') {
                return session.result ?? {
                    success: session.status === 'done',
                    message: session.error,
                    steps: session.steps ?? [],
                };
            }

            await sleep(600);
        }
    }

    async function handleInstallResult(result) {
        store.setProgress(100);
        store.setSteps(result?.steps ?? []);
        store.setInstallResult(result);

        if (!result?.success) {
            throw new Error(result?.message || 'نصب بسته انجام نشد.');
        }

        await appStore.getApps();
        store.setRoutePrompt(result);
        store.setPhase(store.routePrompt.visible ? 'route' : 'success');

        toast({
            title: store.actionLabel + ' با موفقیت انجام شد',
            type: 'success',
        });
    }

    async function confirmInstall() {
        if (!store.filename) {
            return;
        }

        if (!store.canInstall) {
            store.setError('این بسته به‌دلیل مشکلات سازگاری قابل نصب نیست.');
            return;
        }

        try {
            await ensureCustomDatabaseConnection();
        } catch (error) {
            toast({title: errorMessage(error), type: 'error'});
            return;
        }

        store.setPhase('installing');
        store.error = null;
        store.setProgress(0);
        store.setSteps([]);

        try {
            const response = await appAPI.installPackageStart(buildInstallPayload());
            const payload = unwrapPayload(response);

            let result = payload;

            if (payload?.polling && payload?.install_id) {
                result = await pollInstallStatus(payload.install_id);
            }

            await handleInstallResult(result);
        } catch (error) {
            store.setError(errorMessage(error));
        }
    }

    async function checkPrefix() {
        if (!store.meta?.package_name) {
            return;
        }

        store.prefixLoading = true;

        try {
            const response = await appAPI.checkDatabasePrefix({
                prefix: store.database.prefix,
                package_name: store.meta.package_name,
            });
            const status = unwrapPayload(response);

            if (status?.resolved_prefix) {
                store.database.prefix = status.resolved_prefix;
            }

            if (status?.auto_adjusted) {
                store.setPrefixStatus({available: true});
                store.prefixBaseline = store.database.prefix;
                store.prefixDirty = false;
            } else if (status?.error || status?.tables_exist) {
                store.setPrefixStatus(status);
            } else {
                store.setPrefixStatus({available: true});
                store.prefixBaseline = store.database.prefix;
                store.prefixDirty = false;
            }
        } catch (error) {
            store.setPrefixStatus({error: errorMessage(error)});
        } finally {
            store.prefixLoading = false;
        }
    }

    function onPrefixInput(value) {
        store.prefixDirty = String(value ?? '') !== String(store.prefixBaseline ?? '');
    }

    function onPrefixBlur() {
        if (!store.prefixDirty) {
            return;
        }

        checkPrefix();
    }

    async function testDatabaseConnection(showToast = true) {
        store.connectionTesting = true;

        try {
            await appAPI.testDatabaseConnection({...store.database});
            store.markConnectionVerified();

            return true;
        } catch (error) {
            store.clearConnectionVerified();

            if (showToast) {
                toast({title: errorMessage(error), type: 'error'});
            }

            throw error;
        } finally {
            store.connectionTesting = false;
        }
    }

    async function ensureCustomDatabaseConnection() {
        if (!store.useCustomDatabase) {
            return;
        }

        if (!String(store.database.database || '').trim()) {
            throw new Error('نام دیتابیس را وارد کنید.');
        }

        if (store.isConnectionVerified) {
            return;
        }

        await testDatabaseConnection(false);
    }

    async function assignRoute() {
        const path = String(store.routePrompt.path || '').trim();

        if (!path || !store.routePrompt.packageName) {
            store.setError('آدرس مسیریابی را وارد کنید.');
            return;
        }

        try {
            await routerAPI.save({
                path,
                packageName: store.routePrompt.packageName,
            });
            store.routePrompt.visible = false;
            store.setPhase('success');
            toast({title: 'آدرس با موفقیت ثبت شد', type: 'success'});
        } catch (error) {
            store.setError(errorMessage(error));
        }
    }

    function skipRoutePrompt() {
        store.routePrompt.visible = false;
        store.setPhase('success');
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

    watch(() => store.useCustomDatabase, (enabled) => {
        if (!enabled) {
            store.clearConnectionVerified();
        }
    });

    async function previewStagedFile(filename) {
        store.show();
        store.error = null;
        store.meta = null;
        store.filename = filename;
        store.setLoading('در حال بارگذاری اطلاعات بسته…');

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
        checkPrefix,
        onPrefixInput,
        onPrefixBlur,
        testDatabaseConnection,
        assignRoute,
        skipRoutePrompt,
        installStepLabel,
        toggleAdvanced,
    };
}

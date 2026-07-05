import {watch} from 'vue';
import {appAPI} from '@api/app.js';
import {routerAPI} from '@api/router.js';
import {resolveUploadedFilename, uploadPackageFile} from '@utils/pinion.js';
import {installStepLabel, sleep} from '@utils/packageInstall.js';
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

            store.setProgress(100);

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

    async function loadDatabaseDefaults() {
        if (store.databaseDefaultsLoaded) {
            return;
        }

        try {
            const response = await appAPI.databaseDefaults();
            const defaults = unwrapPayload(response);

            if (defaults) {
                store.database = {
                    ...store.database,
                    connection: defaults.connection ?? store.database.connection,
                    host: defaults.host ?? store.database.host,
                    port: defaults.port ?? store.database.port,
                    database: defaults.database ?? store.database.database,
                    username: defaults.username ?? store.database.username,
                };
            }
        } catch {
            // platform defaults are optional
        } finally {
            store.databaseDefaultsLoaded = true;
        }
    }

    async function toggleAdvanced() {
        const next = !store.showAdvanced;
        store.showAdvanced = next;

        if (next && store.meta?.type === 'app') {
            await loadDatabaseDefaults();
        }
    }

    function buildInstallPayload() {
        const payload = {
            filename: store.filename,
        };

        if (store.meta?.type === 'app' && store.showAdvanced) {
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

        try {
            const response = await appAPI.checkDatabasePrefix({
                prefix: store.database.prefix,
                package_name: store.meta.package_name,
            });
            const status = unwrapPayload(response);
            store.setPrefixStatus(status);

            if (status?.resolved_prefix) {
                store.database.prefix = status.resolved_prefix;
            }
        } catch (error) {
            store.setPrefixStatus({error: errorMessage(error)});
        }
    }

    async function testDatabaseConnection() {
        try {
            await appAPI.testDatabaseConnection({...store.database});
            toast({title: 'اتصال به دیتابیس برقرار شد', type: 'success'});
        } catch (error) {
            toast({title: errorMessage(error), type: 'error'});
        }
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
        checkPrefix,
        testDatabaseConnection,
        assignRoute,
        skipRoutePrompt,
        installStepLabel,
        toggleAdvanced,
        loadDatabaseDefaults,
    };
}

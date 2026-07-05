import {defineStore} from 'pinia';

export const usePackageInstallerStore = defineStore('packageInstaller', {
    state: () => ({
        visible: false,
        minimized: false,
        phase: 'idle',
        progress: 0,
        meta: null,
        filename: null,
        error: null,
        pendingFile: null,
        steps: [],
        showAdvanced: false,
        useCustomDatabase: false,
        databaseDefaultsLoaded: false,
        database: {
            connection: 'mysql',
            host: '127.0.0.1',
            port: '3306',
            database: '',
            username: 'root',
            password: '',
            prefix: '',
        },
        prefixStatus: null,
        installResult: null,
        routePrompt: {
            visible: false,
            path: '',
            packageName: '',
        },
    }),
    getters: {
        actionLabel(state) {
            if (!state.meta) {
                return 'نصب بسته';
            }

            if (state.meta.type === 'theme') {
                return state.meta.install_mode === 'update' ? 'بروزرسانی قالب' : 'نصب قالب';
            }

            return state.meta.install_mode === 'update' ? 'بروزرسانی اپلیکیشن' : 'نصب اپلیکیشن';
        },
        typeLabel(state) {
            if (!state.meta) {
                return null;
            }

            if (state.meta.type === 'theme') {
                return 'قالب';
            }

            return 'اپلیکیشن';
        },
        isBusy(state) {
            return state.phase === 'uploading' || state.phase === 'installing';
        },
        canInstall(state) {
            if (!state.meta?.compatibility) {
                return true;
            }

            return state.meta.compatibility.can_install !== false;
        },
        showDatabaseOptions(state) {
            return state.meta?.type === 'app' && Boolean(
                state.meta?.database?.has_migrations
                || state.meta?.database?.needs_prefix_setup
            );
        },
    },
    actions: {
        show() {
            this.visible = true;
            this.minimized = false;
        },
        showWithFile(file) {
            this.visible = true;
            this.minimized = false;
            this.pendingFile = file;
        },
        minimize() {
            if (this.isBusy) {
                this.minimized = true;
                return;
            }

            this.dismiss();
        },
        dismiss() {
            if (this.isBusy) {
                this.minimized = true;
                return;
            }

            this.visible = false;
            this.minimized = false;
            this.reset();
        },
        reset() {
            this.phase = 'idle';
            this.progress = 0;
            this.meta = null;
            this.filename = null;
            this.error = null;
            this.pendingFile = null;
            this.steps = [];
            this.showAdvanced = false;
            this.useCustomDatabase = false;
            this.databaseDefaultsLoaded = false;
            this.prefixStatus = null;
            this.installResult = null;
            this.routePrompt = {
                visible: false,
                path: '',
                packageName: '',
            };
            this.database = {
                connection: 'mysql',
                host: '127.0.0.1',
                port: '3306',
                database: '',
                username: 'root',
                password: '',
                prefix: '',
            };
        },
        setPhase(phase) {
            this.phase = phase;
        },
        setProgress(value) {
            this.progress = Math.min(100, Math.max(0, Number(value) || 0));
        },
        setMeta(meta, filename) {
            this.meta = meta;
            this.filename = filename;

            const defaults = meta?.database?.connection ?? {};
            const resolvedPrefix = meta?.database?.resolved_prefix ?? meta?.database?.suggested_prefix ?? '';

            this.database = {
                connection: defaults.connection ?? 'mysql',
                host: defaults.host ?? '127.0.0.1',
                port: defaults.port ?? '3306',
                database: defaults.database ?? '',
                username: defaults.username ?? 'root',
                password: '',
                prefix: resolvedPrefix,
            };
        },
        setSteps(steps) {
            this.steps = Array.isArray(steps) ? steps : [];
        },
        setPrefixStatus(status) {
            this.prefixStatus = status;
        },
        setInstallResult(result) {
            this.installResult = result;
        },
        setRoutePrompt(result) {
            if (!result?.is_routable || !result?.package_name) {
                this.routePrompt.visible = false;
                return;
            }

            this.routePrompt = {
                visible: true,
                path: '',
                packageName: result.package_name,
            };
        },
        setError(message) {
            this.error = message;
            this.phase = 'error';
        },
    },
});

import {defineStore} from 'pinia';

function databaseConnectionFingerprint(database) {
    return JSON.stringify({
        connection: database.connection ?? '',
        host: database.host ?? '',
        port: database.port ?? '',
        database: database.database ?? '',
        username: database.username ?? '',
        password: database.password ?? '',
    });
}

export const usePackageInstallerStore = defineStore('packageInstaller', {
    state: () => ({
        visible: false,
        minimized: false,
        phase: 'idle',
        progress: 0,
        loadingMessage: '',
        prefixLoading: false,
        connectionTesting: false,
        connectionVerifiedFingerprint: null,
        routeSaving: false,
        meta: null,
        filename: null,
        error: null,
        pendingFile: null,
        steps: [],
        showAdvanced: false,
        useCustomDatabase: false,
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
        prefixBaseline: '',
        prefixDirty: false,
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
            return state.phase === 'uploading'
                || state.phase === 'installing'
                || state.phase === 'loading';
        },
        canInstall(state) {
            if (!state.meta?.compatibility) {
                return true;
            }

            return state.meta.compatibility.can_install !== false;
        },
        showDatabaseOptions(state) {
            return state.meta?.type === 'app' && Boolean(state.meta?.database?.has_migrations);
        },
        isConnectionVerified(state) {
            if (!state.useCustomDatabase || !state.connectionVerifiedFingerprint) {
                return false;
            }

            return state.connectionVerifiedFingerprint === databaseConnectionFingerprint(state.database);
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
            this.loadingMessage = '';
            this.prefixLoading = false;
            this.connectionTesting = false;
            this.connectionVerifiedFingerprint = null;
            this.routeSaving = false;
            this.showAdvanced = false;
            this.useCustomDatabase = false;
            this.prefixStatus = null;
            this.prefixBaseline = '';
            this.prefixDirty = false;
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
        setLoading(message) {
            this.phase = 'loading';
            this.loadingMessage = message || 'لطفاً صبر کنید…';
            this.error = null;
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
            this.prefixBaseline = resolvedPrefix;
            this.prefixDirty = false;
            this.prefixStatus = null;
            this.connectionVerifiedFingerprint = null;

            if (!meta?.database?.has_migrations) {
                this.showAdvanced = false;
                this.useCustomDatabase = false;
            }
        },
        setSteps(steps) {
            this.steps = Array.isArray(steps) ? steps : [];
        },
        setPrefixStatus(status) {
            this.prefixStatus = status;
        },
        markConnectionVerified() {
            this.connectionVerifiedFingerprint = databaseConnectionFingerprint(this.database);
        },
        clearConnectionVerified() {
            this.connectionVerifiedFingerprint = null;
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

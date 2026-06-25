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
        },
        setPhase(phase) {
            this.phase = phase;
        },
        setProgress(value) {
            this.progress = value;
        },
        setMeta(meta, filename) {
            this.meta = meta;
            this.filename = filename;
        },
        setError(message) {
            this.error = message;
            this.phase = 'error';
        },
    },
});

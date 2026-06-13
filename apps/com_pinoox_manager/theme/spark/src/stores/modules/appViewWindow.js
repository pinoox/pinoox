import {defineStore} from 'pinia';

function defaultRect(index = 0) {
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const offset = index * 32;

    return {
        x: Math.round(vw * 0.11) + offset,
        y: Math.round(vh * 0.09) + offset,
        w: Math.round(vw * 0.78),
        h: Math.round(vh * 0.82),
    };
}

export const useAppViewWindowStore = defineStore('appViewWindow', {
    state: () => ({
        sessions: {},
        panelOrder: [],
        minimized: null,
        selectedPackage: null,
        topZ: 10050,
    }),
    getters: {
        floatingPackages(state) {
            return Object.entries(state.sessions)
                .filter(([, session]) => session.mode === 'floating')
                .map(([packageName]) => packageName);
        },
        fullscreenPackage(state) {
            const entry = Object.entries(state.sessions).find(([, session]) => session.mode === 'fullscreen');

            return entry?.[0] ?? null;
        },
        hasFloating(state) {
            return Object.values(state.sessions).some((session) => session.mode === 'floating');
        },
        minimizedPackages(state) {
            return Object.entries(state.sessions)
                .filter(([, session]) => session.mode === 'minimized')
                .map(([packageName]) => packageName);
        },
        minimizedPackages(state) {
            return Object.entries(state.sessions)
                .filter(([, session]) => session.mode === 'minimized')
                .map(([packageName]) => packageName);
        },
        openPackages(state) {
            const packages = [];

            for (const [packageName, session] of Object.entries(state.sessions)) {
                if (
                    session.mode === 'floating'
                    || session.mode === 'fullscreen'
                    || session.mode === 'minimized'
                ) {
                    packages.push(packageName);
                }
            }

            return packages;
        },
        activePackage(state) {
            const fullscreen = Object.entries(state.sessions).find(([, session]) => session.mode === 'fullscreen');

            if (fullscreen) {
                return fullscreen[0];
            }

            let active = null;
            let topZ = -1;

            for (const [packageName, session] of Object.entries(state.sessions)) {
                if (session.mode === 'floating' && session.zIndex > topZ) {
                    topZ = session.zIndex;
                    active = packageName;
                }
            }

            if (active) {
                return active;
            }

            const minimized = Object.entries(state.sessions)
                .filter(([, session]) => session.mode === 'minimized')
                .map(([packageName]) => packageName);

            if (minimized.length === 0) {
                return null;
            }

            if (state.selectedPackage && minimized.includes(state.selectedPackage)) {
                return state.selectedPackage;
            }

            if (state.minimized?.package_name && minimized.includes(state.minimized.package_name)) {
                return state.minimized.package_name;
            }

            return minimized[minimized.length - 1];
        },
        isPackageOpen(state) {
            return (packageName) => {
                const session = state.sessions[packageName];

                if (
                    session
                    && (
                        session.mode === 'floating'
                        || session.mode === 'fullscreen'
                        || session.mode === 'minimized'
                    )
                ) {
                    return true;
                }

                return false;
            };
        },
    },
    actions: {
        registerPanel(packageName) {
            if (!this.panelOrder.includes(packageName)) {
                this.panelOrder.push(packageName);
            }
        },
        ensureSession(packageName) {
            if (!this.sessions[packageName]) {
                const index = Object.keys(this.sessions).length;

                this.sessions[packageName] = {
                    mode: 'hidden',
                    rect: defaultRect(index),
                    zIndex: this.topZ + 1,
                };
                this.topZ += 1;
            }
        },
        selectPackage(packageName) {
            this.selectedPackage = packageName;
        },
        focus(packageName) {
            if (!this.sessions[packageName]) {
                return;
            }

            this.selectedPackage = packageName;
            this.topZ += 1;
            this.sessions[packageName].zIndex = this.topZ;
        },
        focusFloating(packageName) {
            this.ensureSession(packageName);

            const session = this.sessions[packageName];

            if (session.mode === 'minimized') {
                this.minimized = null;
            }

            if (session.mode === 'fullscreen' || session.mode === 'minimized' || session.mode === 'hidden') {
                session.mode = 'floating';
            }

            this.focus(packageName);
            this.registerPanel(packageName);
        },
        updateRect(packageName, rect) {
            if (!this.sessions[packageName]) {
                return;
            }

            this.sessions[packageName].rect = {...rect};
        },
        openFullscreen(packageName) {
            this.ensureSession(packageName);

            for (const [pkg, session] of Object.entries(this.sessions)) {
                if (session.mode === 'fullscreen' && pkg !== packageName) {
                    session.mode = 'floating';
                }
            }

            this.sessions[packageName].mode = 'fullscreen';
            this.focus(packageName);
            this.minimized = null;
            this.registerPanel(packageName);
        },
        enterFloating(packageName) {
            this.ensureSession(packageName);
            this.sessions[packageName].mode = 'floating';
            this.focus(packageName);
            this.minimized = null;
            this.registerPanel(packageName);
        },
        hideSession(packageName) {
            if (this.sessions[packageName]) {
                this.sessions[packageName].mode = 'hidden';
            }
        },
        closeSession(packageName) {
            if (this.sessions[packageName]) {
                delete this.sessions[packageName];
            }

            this.panelOrder = this.panelOrder.filter((pkg) => pkg !== packageName);

            if (this.minimized?.package_name === packageName) {
                this.minimized = null;
            }

            if (this.selectedPackage === packageName) {
                this.selectedPackage = null;
            }
        },
        minimize(snapshot) {
            this.ensureSession(snapshot.package_name);

            const session = this.sessions[snapshot.package_name];
            const restoreMode = snapshot.restoreMode === 'fullscreen' || session.mode === 'fullscreen'
                ? 'fullscreen'
                : 'floating';

            this.minimized = {
                package_name: snapshot.package_name,
                icon: snapshot.icon ?? '',
                appName: snapshot.appName ?? snapshot.package_name,
                restoreMode,
            };
            session.mode = 'minimized';
            this.selectedPackage = snapshot.package_name;
        },
        restoreSession(packageName) {
            let restoreMode = 'floating';

            if (this.minimized?.package_name === packageName) {
                restoreMode = this.minimized.restoreMode === 'fullscreen' ? 'fullscreen' : 'floating';
                this.minimized = null;
            }

            this.ensureSession(packageName);

            if (restoreMode === 'fullscreen') {
                this.openFullscreen(packageName);
            } else {
                this.focusFloating(packageName);
            }

            return restoreMode;
        },
        restore() {
            this.minimized = null;
        },
        dismissAll() {
            this.sessions = {};
            this.panelOrder = [];
            this.minimized = null;
            this.selectedPackage = null;
        },
        demoteFullscreenIfNeeded(routeName) {
            if (routeName === 'app-view') {
                return;
            }

            for (const session of Object.values(this.sessions)) {
                if (session.mode === 'fullscreen') {
                    session.mode = 'floating';
                }
            }
        },
    },
});

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
        minimized: null,
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

            return state.minimized?.package_name ?? null;
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
        focus(packageName) {
            if (!this.sessions[packageName]) {
                return;
            }

            this.topZ += 1;
            this.sessions[packageName].zIndex = this.topZ;
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
        },
        enterFloating(packageName) {
            this.ensureSession(packageName);
            this.sessions[packageName].mode = 'floating';
            this.focus(packageName);
            this.minimized = null;
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

            if (this.minimized?.package_name === packageName) {
                this.minimized = null;
            }
        },
        minimize(snapshot) {
            this.ensureSession(snapshot.package_name);
            this.minimized = {
                package_name: snapshot.package_name,
                icon: snapshot.icon ?? '',
                appName: snapshot.appName ?? snapshot.package_name,
            };
            this.sessions[snapshot.package_name].mode = 'minimized';
        },
        restore() {
            this.minimized = null;
        },
        dismissAll() {
            this.sessions = {};
            this.minimized = null;
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

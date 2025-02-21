import { defineStore } from 'pinia';
import { appAPI } from "@api/app.js";

export const useAppStore = defineStore({
    id: 'app',
    state: () => ({
        apps: {},
        isLoaded: false,
    }),
    getters: {
        appList() {
            return Object.values(this.apps);
        },
    },
    actions: {
        async getApps() {
            return new Promise((resolve, reject) => {
                appAPI.getAll().then((response) => {
                    this.setApps(response.data);
                    this.isLoaded = true;
                    resolve();
                }).catch(error => {
                    reject(error);
                });
            });
        },
        destroyApps() {
            this.apps = {};
            this.isLoaded = false;
        },
        setApps(apps) {
            this.apps = apps;
        },
        deleteAppByPackage(packageName) {
            if (this.apps[packageName]) {
                delete this.apps[packageName];
            }
        },
        addApp(app) {
            if (app.package_name) {
                this.apps[app.package_name] = app;
            }
        },
        setAppByPackage(packageName, appData) {
            if (this.apps[packageName]) {
                this.apps[packageName] = { ...this.apps[packageName], ...appData };
            }
        },
        fetchAppByPackage(packageName) {
            return this.apps[packageName] || null;
        },
        fetchAppsLikeName(name) {
            return Object.values(this.apps).filter(app => app.name.toLowerCase().includes(name.toLowerCase()));
        }
    },
});

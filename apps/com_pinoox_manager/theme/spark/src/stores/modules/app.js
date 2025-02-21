import {defineStore} from 'pinia';
import {appAPI} from "@api/app.js";

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
    },
});

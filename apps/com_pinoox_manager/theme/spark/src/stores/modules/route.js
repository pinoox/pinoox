import {defineStore} from 'pinia';
import {routerAPI} from "@api/router.js";

export const useRouteStore = defineStore({
    id: 'route',
    state: () => ({
        routes: {},
        isLoaded: false,
    }),
    getters: {
        routeList() {
            return Object.values(this.routes);
        },
    },
    actions: {
        async getRoutes() {
            return new Promise((resolve, reject) => {
                routerAPI.getAll().then((response) => {
                    this.setRoutes(response.data);
                    this.isLoaded = true;
                    resolve();
                }).catch(error => {
                    reject(error);
                });
            });
        },
        destroyRoutes() {
            this.routes = {};
            this.isLoaded = false;
        },
        setRoutes(routes) {
            this.routes = routes;
        },
        saveRoute(path, packageName, oldPath) {
            this.deleteRouteByPath(oldPath);
            this.addRoute(path, {
                path: path,
                package: packageName,
            });
        },
        addRoute(path, routeData) {
            this.routes[path] = routeData;
        },
        setRouteByPath(path, routeData) {
            if (this.routes[path]) {
                this.routes[path] = {...this.routes[path], ...routeData};
            }
        },
        fetchRouteByPath(path) {
            return this.routes[path] || null;
        },
        fetchRoutesByPackage(packageName) {
            return Object.values(this.routes).filter(route => route.package === packageName);
        },
        deleteRouteByPath(path) {
            if (this.routes[path]) {
                delete this.routes[path];
            }
        },
        deleteRoutesByPackage(packageName) {
            Object.keys(this.routes).forEach(path => {
                if (this.routes[path].package === packageName) {
                    delete this.routes[path];
                }
            });
        },
    },
});

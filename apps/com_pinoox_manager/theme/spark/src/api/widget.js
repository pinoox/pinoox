import {http} from "@global";

const BASE_URL = '/widget';

export const widgetAPI = {
    clock: () => http.get(`${BASE_URL}/clock`, {alert: false}),
    storage: () => http.get(`${BASE_URL}/storage`, {alert: false}),
    settings: () => http.get(`${BASE_URL}/settings`, {alert: false}),
    browseStorage: (path) => http.get(`${BASE_URL}/storageBrowse`, {
        params: path ? { path } : {},
        alert: false,
    }),
    saveWidgets: (payload) => http.post(`${BASE_URL}/saveWidgets`, payload, {alert: false}),
    saveStorageSettings: (payload) => http.post(`${BASE_URL}/storageSettings`, payload, {alert: false}),
};

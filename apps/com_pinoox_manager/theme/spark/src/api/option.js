import {http} from "@global";

const BASE_URL = '/options';

export const optionAPI = {
    get: () => http.get(`${BASE_URL}/get`, {alert: false}),
    changeBackground: (name) => http.get(`${BASE_URL}/changeBackground/${name}`, {alert: false}),
    uploadWallpaper: (formData) => http.postForm(`${BASE_URL}/uploadWallpaper`, formData, {alert: false}),
    deleteWallpaper: (name) => http.post(`${BASE_URL}/deleteWallpaper/${name}`, {}, {alert: false}),
    changeLockTime: (minutes) => http.get(`${BASE_URL}/changeLockTime/${minutes}`, {alert: false}),
    changeLang: (lang) => http.get(`/changeLang/${lang}`, {alert: false}),
    toggleDockPin: (packageName) => http.get(`${BASE_URL}/toggleDockPin/${packageName}`, {alert: false}),
};

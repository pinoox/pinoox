import {http} from "@global";

const BASE_URL = '/options';

export const optionAPI = {
    get: () => http.get(`${BASE_URL}/get`, {alert: false}),
    changeBackground: (name) => http.get(`${BASE_URL}/changeBackground/${name}`),
    uploadWallpaper: (formData) => http.postForm(`${BASE_URL}/uploadWallpaper`, formData),
    deleteWallpaper: (name) => http.post(`${BASE_URL}/deleteWallpaper/${name}`, {}),
    changeLockTime: (minutes) => http.get(`${BASE_URL}/changeLockTime/${minutes}`),
    changeLang: (lang) => http.get(`/changeLang/${lang}`, {alert: false}),
    toggleDockPin: (packageName) => http.get(`${BASE_URL}/toggleDockPin/${packageName}`, {alert: false}),
    changeAppViewMode: (mode) => http.get(`${BASE_URL}/changeAppViewMode/${mode}`),
};

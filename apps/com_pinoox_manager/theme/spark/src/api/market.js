import {http} from "@global";

const BASE_URL = '/market';

export const marketAPI = {
    getDownloads: () => http.get(`${BASE_URL}/getDownloads`, {alert: false}),
    deleteDownload: (package_name) => http.post(`${BASE_URL}/deleteDownload`, {package_name}),
    getApps: (keyword = '') => http.get(`${BASE_URL}/getApps/${keyword}`, {alert: false}),
    getOneApp: (package_name) => http.get(`${BASE_URL}/getOneApp/${package_name}`, {alert: false}),
    downloadRequest: (package_name, auth) => http.post(`${BASE_URL}/downloadRequest/${package_name}`, {auth}),
    getTemplates: (package_name) => http.get(`${BASE_URL}/getTemplates/${package_name}`, {alert: false}),
    downloadRequestTemplate: (uid, data) => http.post(`${BASE_URL}/downloadRequestTemplate/${uid}`, data),
};

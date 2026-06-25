import {http} from "@global";

const BASE_URL = '/app';

export const appAPI = {
    install: (params) => http.postForm(BASE_URL + '/install', params),
    getAll: () => http.get(BASE_URL + '/getAll', {alert: false}),
    iconPack: () => http.get(BASE_URL + '/iconPack', {alert: false}),
    get: (filter) => http.get(BASE_URL + '/get' + (filter ? `/${filter}` : ''), {alert: false}),
    getConfig: (packageName) => http.get(`${BASE_URL}/getConfig/${packageName}`, {alert: false}),
    setConfig: (packageName, key, config) => http.post(`${BASE_URL}/setConfig/${packageName}/${key}`, {config}),
    installPackage: (filename) => http.get(`${BASE_URL}/installPackage/${encodeURIComponent(filename)}`),
    packageMeta: (filename) => http.get(`${BASE_URL}/packageMeta/${encodeURIComponent(filename)}`, {alert: false}),
    files: () => http.get(`${BASE_URL}/files`, {alert: false}),
    deleteFile: (filename) => http.post(`${BASE_URL}/deleteFile`, {filename}),
    filesUpload: (formData) => http.postForm(`${BASE_URL}/filesUpload`, formData),
    remove: (packageName) => http.post(`${BASE_URL}/remove/${packageName}`),
};
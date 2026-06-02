import {http} from "@global";

const BASE_URL = '/user';

export const userAPI = {
    get: () => http.get(`${BASE_URL}/get`, {alert: false}),
    getOptions: () => http.get(`${BASE_URL}/getOptions`, {alert: false}),
    deleteAvatar: () => http.get(`${BASE_URL}/deleteAvatar`),
    changeAvatar: (formData) => http.postForm(`${BASE_URL}/changeAvatar`, formData),
    changeInfo: (params) => http.post(`${BASE_URL}/changeInfo`, params),
    changePassword: (params) => http.post(`${BASE_URL}/changePassword`, params),
    getUsers: (packageName) => http.get(`${BASE_URL}/getUsers/${packageName}`, {alert: false}),
};

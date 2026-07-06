import {http} from "@global";
import {HTTP_ALERT_SUCCESS} from '@utils/helpers/alertHelper.js';

const BASE_URL = '/user';

export const userAPI = {
    get: () => http.get(`${BASE_URL}/get`, {alert: false}),
    getOptions: () => http.get(`${BASE_URL}/getOptions`, {alert: false}),
    deleteAvatar: () => http.get(`${BASE_URL}/deleteAvatar`, HTTP_ALERT_SUCCESS),
    changeAvatar: (formData) => http.postForm(`${BASE_URL}/changeAvatar`, formData, HTTP_ALERT_SUCCESS),
    changeInfo: (params) => http.post(`${BASE_URL}/changeInfo`, params, HTTP_ALERT_SUCCESS),
    changePassword: (params) => http.post(`${BASE_URL}/changePassword`, params, HTTP_ALERT_SUCCESS),
    getUsers: (packageName) => http.get(`${BASE_URL}/getUsers/${packageName}`, {alert: false}),
};

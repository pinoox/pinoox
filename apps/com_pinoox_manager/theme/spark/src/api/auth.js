import {http} from "@global";

const BASE_URL = '/auth';

export const authAPI = {
    login: (params) => http.post(BASE_URL + '/login', params),
    verify: (params) => http.post(BASE_URL + '/verify', params),
    get: () => http.get(`${BASE_URL}/get`, {alert: false}),
    logout: () => http.get(`${BASE_URL}/logout`, {alert: false})
};
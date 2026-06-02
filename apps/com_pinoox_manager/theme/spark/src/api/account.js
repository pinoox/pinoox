import {http} from "@global";

const BASE_URL = '/account';

export const accountAPI = {
    getConnectData: () => http.get(`${BASE_URL}/getConnectData`, {alert: false}),
    connect: () => http.get(`${BASE_URL}/connect`),
    logout: () => http.get(`${BASE_URL}/logout`),
};

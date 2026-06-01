import {http} from "@global";

const BASE_URL = '/update';

export const updateAPI = {
    checkVersion: (force = false) => http.get(`${BASE_URL}/checkVersion/${force ? 'force' : 'none'}`, {alert: false}),
    install: () => http.get(`${BASE_URL}/install`),
};

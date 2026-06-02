import {http} from "@global";

const BASE_URL = '/notification';

export const notificationAPI = {
    getAll: () => http.get(BASE_URL, {alert: false}),
    hide: (ntf_id) => http.post(`${BASE_URL}/hide`, {ntf_id}),
    seen: (notifications) => http.post(`${BASE_URL}/seen`, {notifications}),
};

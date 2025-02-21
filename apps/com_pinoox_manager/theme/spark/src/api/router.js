import {http} from "@global";

const BASE_URL = '/router';

export const routerAPI = {
    getAll: () => http.get(BASE_URL + '/getAll'),
    add: (params) => http.post(BASE_URL + '/add', params),
    remove: (params) => http.post(BASE_URL + '/remove', params),
    setPackageName: (params) => http.post(BASE_URL + '/setPackageName', params),
};
import {http} from "@global";

const BASE_URL = '/router';

export const routerAPI = {
    getAll: () => http.get(BASE_URL + '/getAll'),
    remove: (params) => http.post(BASE_URL + '/remove', params),
    save: (params) => http.post(BASE_URL + '/save', params),
};
import {http} from "@global";

const BASE_URL = '/app';

export const appAPI = {
    install: (params) => http.postForm(BASE_URL + '/install', params),
    getAll: () => http.get(BASE_URL + '/getAll'),
};
import {http} from "@global";
import {HTTP_ALERT_SILENT} from '@utils/helpers/alertHelper.js';

const BASE_URL = '/router';

export const routerAPI = {
    getAll: () => http.get(BASE_URL + '/getAll', {alert: false}),
    remove: (params) => http.post(BASE_URL + '/remove', params),
    save: (params) => http.post(BASE_URL + '/save', params, HTTP_ALERT_SILENT),
};
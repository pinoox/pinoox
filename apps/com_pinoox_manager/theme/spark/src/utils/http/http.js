import axios from "axios";
import { getUrl } from '@/boot.js';
import {readApiErrorMessage} from "@utils/apiEnvelope.js";
import {showSuccessAlert, showErrorAlert} from '@utils/helpers/alertHelper.js';

const baseUrl = getUrl().API || import.meta.env.VITE_API_PATH;

function getTokenAuth() {
    let token = localStorage.manager_pinoox;
    if (!!token) {
        return `${token}`;
    }
    return null;
}

const actions = {
    start: [],
    stop: [],
    error: [],
    error_request: [],
    error_response: [],
    request: [],
    response: [],
}

const callActions = (actions, value) => {
    for (let action of actions)
        action(value);
};

const http = axios.create({
    baseURL: baseUrl,
    numProcessing: 0,
    error: true,
});

export const event = (event_name, func) => {
    actions[event_name].push(func);
};

http.event = event;

http.interceptors.request.use((request) => {

    callActions(actions.request, request);

    if (request.numProcessing === 0) {
        callActions(actions.start, request);
    }
    request.numProcessing++;

    request.headers.Authorization = getTokenAuth();

    return request;
}, function (error) {
    callActions(actions.error_request, error);
    callActions(actions.error, error);
    return Promise.reject(error);
});

http.interceptors.response.use((response) => {
    if (response.config?.alert !== false) {
        showSuccessAlert(response);
    }
    callActions(actions.response, response);

    response.config.numProcessing--;

    if (response.config.numProcessing === 0) {
        callActions(actions.stop, response);
    }

    return response;
}, function (error) {
    if (error.config) {
        error.config.numProcessing--;
    }

    if (error.config?.alert !== false) {
        showErrorAlert(error);
    }
    callActions(actions.error_response, error);
    callActions(actions.error, error);

    return Promise.reject(error);
});

http.token = getTokenAuth();

http.postForm = (url, data, config = {}) => {
    const formData = data instanceof FormData ? data : data;
    return http.post(url, formData, {
        ...config,
        headers: {
            ...(config.headers || {}),
            'Content-Type': 'multipart/form-data',
        },
    });
};

window.$http = http;
export default http;

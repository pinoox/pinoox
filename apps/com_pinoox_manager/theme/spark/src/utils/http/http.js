import axios from "axios";

const baseUrl = import.meta.env.MODE === 'production' ? PINOOX.URL.API : import.meta.env.VITE_API_PATH;

function getTokenAuth() {
    let token = localStorage.pinoox_user;
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
    callActions(actions.response, response);

    response.config.numProcessing--;

    if (response.config.numProcessing === 0) {
        callActions(actions.stop, response);
    }

    return response;
}, function (error) {
    error.config.numProcessing--;

    callActions(actions.error_response, error);
    callActions(actions.error, error);

    if (!error.config.error)
        return Promise.reject(error);
    else
        return Promise.reject(error.response.data.error);
});

http.token = getTokenAuth();
window.$http = http;
export default http;

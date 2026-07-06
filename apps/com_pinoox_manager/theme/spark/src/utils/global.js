import httpAxios, {event} from './http/http.js';
import {toast} from '@utils/helpers/toastHelper.js';

export const http = httpAxios;
export const httpEvent = event;

export const delay = (() => {
    let timers = {};
    return (callback, ms, key = 'default') => {
        if (key in timers) {
            clearTimeout(timers[key]);
        }
        timers[key] = setTimeout(callback, ms);
        return timers[key];
    };
})();

export {toast};

export default {
    http,
    toast,
    httpEvent,
    delay,
};

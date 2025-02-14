import {toast} from "@/utils/global.js";

export function showSuccessAlert(response) {
    if (
        !response ||
        !response.data.message ||
        typeof response.data.message !== 'string' || // Check if message is not a string
        response.config.alert === false
    ) return;

    toast({
        title: response.data.message,
        type: 'success',
    });
}

export function showErrorAlert(error) {
    if (!error.response || error.config.alert === false) return;

    let res = error.response;
    let type = (res.status >= 300 && res.status <= 400) ? 'warn' : 'error';

    toast({
        title: error.response.data.error,
        type: type,
    });
}
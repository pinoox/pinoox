import {toastSuccess, toastError, toastWarn} from '@utils/helpers/toastHelper.js';
import {isApiEnvelope, readApiErrorFromBody, readApiErrorMessage, readApiMessage} from '@utils/apiEnvelope.js';

/** Suppresses interceptor toasts (success and HTTP error). Use when the caller handles feedback. */
export const HTTP_ALERT_SILENT = {alert: false};

/** Enables success toast from interceptor; errors still show unless alert is false. */
export const HTTP_ALERT_SUCCESS = {successAlert: true};

function shouldShowErrorAlert(config) {
    return config?.alert !== false;
}

function shouldShowSuccessAlert(config) {
    return config?.successAlert === true;
}

export function showSuccessAlert(response) {
    if (!response) {
        return;
    }

    const config = response.config;
    const body = response.data;

    if (body == null || typeof body !== 'object') {
        return;
    }

    if (isApiEnvelope(body) && body.success === false) {
        if (shouldShowErrorAlert(config)) {
            toastError(readApiErrorFromBody(body) || readApiErrorMessage({response}));
        }
        return;
    }

    if (!shouldShowSuccessAlert(config)) {
        return;
    }

    const message = readApiMessage(body);

    if (!message) {
        return;
    }

    if (isApiEnvelope(body) && body.data === false) {
        toastError(message);
        return;
    }

    toastSuccess(message);
}

export function showErrorAlert(error) {
    if (!shouldShowErrorAlert(error.config)) {
        return;
    }

    const title = readApiErrorMessage(error);

    if (!title) {
        return;
    }

    const status = error.response?.status;

    if (status >= 300 && status <= 400) {
        toastWarn(title);
    } else {
        toastError(title);
    }
}

import {toastSuccess, toastError, toastWarn} from '@utils/helpers/toastHelper.js';
import {isApiEnvelope, readApiErrorMessage, readApiMessage} from '@utils/apiEnvelope.js';

function shouldShowAlert(config) {
    return config?.alert !== false;
}

export function showSuccessAlert(response) {
    if (!response || !shouldShowAlert(response.config)) {
        return;
    }

    const body = response.data;

    if (body == null || typeof body !== 'object') {
        return;
    }

    const message = readApiMessage(body);

    if (isApiEnvelope(body) && body.success === false) {
        toastError(message || readApiErrorMessage({response}));
        return;
    }

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
    if (!shouldShowAlert(error.config)) {
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

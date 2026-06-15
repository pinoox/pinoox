import {toastSuccess, toastError, toastWarn} from '@utils/helpers/toastHelper.js';
import {isApiEnvelope, readApiErrorMessage, readApiMessage} from '@utils/apiEnvelope.js';

function shouldShowAlert(config) {
    return config?.alert !== false;
}

function resolveFlashMessage(body) {
    const message = readApiMessage(body);

    if (typeof message === 'string' && message.length > 0) {
        return message;
    }

    return null;
}

export function showSuccessAlert(response) {
    if (!response || !shouldShowAlert(response.config)) {
        return;
    }

    const body = response.data;

    if (isApiEnvelope(body) && body.success === false) {
        const title = readApiErrorMessage({response});

        if (title) {
            toastError(title);
        }

        return;
    }

    const title = resolveFlashMessage(body);

    if (!title) {
        return;
    }

    if (isApiEnvelope(body) && body.data === false) {
        toastWarn(title);
        return;
    }

    toastSuccess(title);
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

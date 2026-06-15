import {toastSuccess, toastError, toastWarn} from '@utils/helpers/toastHelper.js';
import {readApiErrorMessage, readApiMessage} from '@utils/apiEnvelope.js';

export function showSuccessAlert(response) {
    if (!response || response.config?.alert === false) return;

    const title = readApiMessage(response.data);
    if (!title || typeof title !== 'string') return;

    toastSuccess(title);
}

export function showErrorAlert(error) {
    if (!error.response || error.config?.alert === false) return;

    const res = error.response;
    const title = readApiErrorMessage(error);

    if (res.status >= 300 && res.status <= 400)
        toastWarn(title);
    else
        toastError(title);
}

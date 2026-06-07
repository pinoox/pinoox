import {toast} from "@/utils/global.js";
import {isApiEnvelope, readApiErrorMessage, readApiMessage} from "@utils/apiEnvelope.js";

export function showSuccessAlert(response) {
    if (!response || response.config?.alert === false) return;

    const title = readApiMessage(response.data);
    if (!title || typeof title !== 'string') return;

    toast({
        title,
        type: 'success',
    });
}

export function showErrorAlert(error) {
    if (!error.response || error.config?.alert === false) return;

    const res = error.response;
    const type = (res.status >= 300 && res.status <= 400) ? 'warn' : 'error';

    toast({
        title: readApiErrorMessage(error),
        type: type,
    });
}

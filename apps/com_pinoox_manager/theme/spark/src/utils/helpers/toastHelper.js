import { notify as packageNotify } from '@kyvg/vue3-notification';

let notifyImpl = packageNotify;

export function bindNotify(notify) {
    if (typeof notify === 'function') {
        notifyImpl = notify;
    }
}

function dispatch(options) {
    notifyImpl(options);
}

const DEFAULT_OPTIONS = {
    duration: 5200,
    ignoreDuplicates: false,
    pauseOnHover: true,
    closeOnClick: false,
    group: '',
};

export function toast(options) {
    if (typeof options === 'string') {
        return dispatch({
            ...DEFAULT_OPTIONS,
            title: options,
            type: 'info',
        });
    }

    return dispatch({
        ...DEFAULT_OPTIONS,
        ...options,
    });
}

export function toastSuccess(title, text = '') {
    return toast({title, text, type: 'success'});
}

export function toastError(title, text = '') {
    return toast({title, text, type: 'error'});
}

export function toastWarn(title, text = '') {
    return toast({title, text, type: 'warn'});
}

export function toastInfo(title, text = '') {
    return toast({title, text, type: 'info'});
}

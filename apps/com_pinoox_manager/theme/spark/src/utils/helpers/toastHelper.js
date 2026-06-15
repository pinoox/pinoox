import {notify} from '@kyvg/vue3-notification';

const DEFAULT_OPTIONS = {
    duration: 5200,
    ignoreDuplicates: false,
    pauseOnHover: true,
    closeOnClick: false,
};

export function toast(options) {
    if (typeof options === 'string') {
        return notify({
            ...DEFAULT_OPTIONS,
            title: options,
            type: 'info',
        });
    }

    return notify({
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

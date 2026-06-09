import { isApiEnvelope, unwrapApiBody } from '@utils/apiEnvelope.js';

/**
 * Normalize Pinoox API responses (standard envelope, legacy { result, message }, or raw data).
 */
export function unwrapResponse(response) {
    const data = response?.data;
    if (data == null) return data;

    if (isApiEnvelope(data)) {
        return unwrapApiBody(data, {status: response?.status ?? null});
    }

    if (Object.prototype.hasOwnProperty.call(data, 'result')) {
        return data.result;
    }

    if (
        Object.prototype.hasOwnProperty.call(data, 'message')
        && typeof data.message === 'object'
        && data.message !== null
    ) {
        return data.message;
    }

    return data;
}

export function unwrapList(response) {
    const data = unwrapResponse(response);
    if (Array.isArray(data)) return data;
    if (data && Array.isArray(data.apps)) return data.apps;
    if (data && Array.isArray(data.items)) return data.items;
    return [];
}

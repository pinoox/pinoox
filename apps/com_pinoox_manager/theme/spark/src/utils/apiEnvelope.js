export class ApiClientError extends Error {
    constructor(message, {code = 'API_ERROR', details = {}, status = null} = {}) {
        super(message)
        this.name = 'ApiClientError'
        this.code = code
        this.details = details
        this.status = status
    }
}

export function isApiEnvelope(body) {
    return body !== null && typeof body === 'object' && ('success' in body);
}

function normalizeApiBody(body) {
    if (body == null) {
        return null;
    }

    if (typeof body === 'string') {
        try {
            return normalizeApiBody(JSON.parse(body));
        } catch {
            return null;
        }
    }

    if (typeof body !== 'object') {
        return null;
    }

    if ('success' in body) {
        return body;
    }

    if (body.data && typeof body.data === 'object' && ('success' in body.data)) {
        return body.data;
    }

    return body;
}

export function readApiErrorFromBody(body) {
    const envelope = normalizeApiBody(body);

    if (!envelope || typeof envelope !== 'object') {
        return null;
    }

    if (envelope.success === false && envelope.error) {
        if (typeof envelope.error === 'string' && envelope.error.trim()) {
            return envelope.error.trim();
        }

        if (typeof envelope.error?.message === 'string' && envelope.error.message.trim()) {
            const message = envelope.error.message.trim();
            return message !== 'OK' ? message : (envelope.error.code || null);
        }

        if (typeof envelope.error?.code === 'string' && envelope.error.code.trim()) {
            return envelope.error.code.trim();
        }
    }

    if (typeof envelope.message === 'string' && envelope.message.trim() && envelope.message.trim() !== 'OK') {
        return envelope.message.trim();
    }

    if (typeof envelope.error === 'string' && envelope.error.trim()) {
        return envelope.error.trim();
    }

    return null;
}

export function resolveApiFailure(error) {
    if (typeof error === 'string' && error.trim()) {
        return error.trim();
    }

    if (error instanceof ApiClientError && error.message?.trim()) {
        return error.message.trim();
    }

    if (error?.name === 'ApiClientError' && typeof error.message === 'string' && error.message.trim()) {
        return error.message.trim();
    }

    const fromResponse = readApiErrorFromBody(error?.response?.data);
    if (fromResponse) {
        return fromResponse;
    }

    if (typeof error?.message === 'string' && error.message.trim() && error.message !== 'Request failed') {
        return error.message.trim();
    }

    return readApiErrorMessage(error);
}

export function unwrapApiBody(body, {status = null} = {}) {
    if (!isApiEnvelope(body)) {
        return body
    }

    if (!body.success) {
        const message = readApiErrorFromBody(body) || body.error?.message || 'Request failed';
        const error = body.error || {};

        throw new ApiClientError(message, {
            code: error.code || 'API_ERROR',
            details: error.details || {},
            status,
        })
    }

    return body.data ?? null
}

export function unwrapApiResponse(response) {
    return unwrapApiBody(response?.data, {status: response?.status ?? null})
}

function isDisplayMessage(message) {
    if (typeof message !== 'string') {
        return false;
    }

    const text = message.trim();

    return text.length > 0 && text !== 'OK';
}

export function readApiMessage(body) {
    if (body == null || typeof body !== 'object') {
        return null;
    }

    if (!isApiEnvelope(body)) {
        if (isDisplayMessage(body.message)) {
            return body.message.trim();
        }

        return null;
    }

    if (body.success !== true) {
        if (isDisplayMessage(body.message)) {
            return body.message.trim();
        }

        return null;
    }

    if (isDisplayMessage(body.message)) {
        return body.message.trim();
    }

    if (isDisplayMessage(body.meta?.message)) {
        return body.meta.message.trim();
    }

    return null;
}

export function readApiErrorMessage(error) {
    if (typeof error === 'string' && error.trim()) {
        return error.trim();
    }

    if (error instanceof ApiClientError && error.message?.trim()) {
        return error.message.trim();
    }

    const fromBody = readApiErrorFromBody(error?.response?.data);
    if (fromBody) {
        return fromBody;
    }

    const body = error?.response?.data;

    if (typeof body?.error === 'string') {
        return body.error;
    }

    if (typeof body?.error?.message === 'string' && body.error.message.trim()) {
        return body.error.message.trim();
    }

    return error?.message || 'Request failed';
}

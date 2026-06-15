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
    return body !== null && typeof body === 'object' && typeof body.success === 'boolean'
}

export function unwrapApiBody(body, {status = null} = {}) {
    if (!isApiEnvelope(body)) {
        return body
    }

    if (!body.success) {
        const error = body.error || {}

        throw new ApiClientError(error.message || 'Request failed', {
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
    const body = error?.response?.data

    if (isApiEnvelope(body) && body.error) {
        return body.error.message || body.error.code || 'Request failed'
    }

    if (typeof body?.error === 'string') {
        return body.error
    }

    if (typeof body?.error?.message === 'string') {
        return body.error.message
    }

    return error?.message || 'Request failed'
}

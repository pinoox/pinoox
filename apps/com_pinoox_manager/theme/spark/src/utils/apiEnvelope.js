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

export function readApiMessage(body) {
    if (!isApiEnvelope(body)) {
        return typeof body?.message === 'string' ? body.message : null
    }

    return typeof body.message === 'string' && body.message !== 'OK' ? body.message : null
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

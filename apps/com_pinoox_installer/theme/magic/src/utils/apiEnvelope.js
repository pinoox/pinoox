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

export function normalizeApiError(error) {
    if (error instanceof ApiClientError) {
        return error
    }

    const body = error?.response?.data
    const status = error?.response?.status ?? null

    if (isApiEnvelope(body) && body.success === false) {
        const apiError = body.error || {}

        return new ApiClientError(apiError.message || apiError.code || 'Request failed', {
            code: apiError.code || 'API_ERROR',
            details: apiError.details || {},
            status,
        })
    }

    return error
}

export function readApiErrorMessage(error, fallback = 'Request failed') {
    const normalized = normalizeApiError(error)

    if (normalized instanceof ApiClientError) {
        return normalized.message || fallback
    }

    return error?.message || fallback
}

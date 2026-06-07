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

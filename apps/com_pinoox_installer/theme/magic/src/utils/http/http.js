import axios from 'axios'
import axiosMethodOverride from 'axios-method-override'
import {normalizeApiError} from '@/utils/apiEnvelope.js'
import {
    QUERY_ROUTE_PARAM,
    resolveInstallerApiUrl,
    resolvePingApiUrl,
    shouldUseQueryRoute,
} from '../resolveInstallerApi.js'

const baseUrl = import.meta.env.MODE === 'production'
    ? (typeof PINOOX !== 'undefined' ? PINOOX.URL.API : '')
    : import.meta.env.VITE_API_PATH

const actions = {
    start: [],
    stop: [],
    error: [],
    error_request: [],
    error_response: [],
    request: [],
    response: [],
}

let pendingRequests = 0

const callActions = (name, value) => {
    for (const action of actions[name]) {
        action(value)
    }
}

const trackLoading = (config, delta) => {
    if (config.loading === false) {
        return
    }

    pendingRequests += delta

    if (delta > 0 && pendingRequests === 1) {
        callActions('start', config)
    }

    if (delta < 0 && pendingRequests === 0) {
        callActions('stop', config)
    }
}

function applyQueryRoute(config) {
    if (!config.url || String(config.url).includes(`?${QUERY_ROUTE_PARAM}=`)) {
        return
    }

    const rawUrl = String(config.url)

    if (/^https?:\/\//i.test(rawUrl)) {
        return
    }

    const endpoint = rawUrl.replace(/^\//, '')

    if (endpoint === 'ping') {
        config.baseURL = ''
        config.url = resolvePingApiUrl()
        return
    }

    if (!shouldUseQueryRoute()) {
        return
    }

    config.baseURL = ''
    config.url = resolveInstallerApiUrl(endpoint)
}
const http = axios.create({
    baseURL: baseUrl,
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
})

axiosMethodOverride(http)

export const event = (eventName, func) => {
    actions[eventName].push(func)
}

http.event = event

http.interceptors.request.use((config) => {
    applyQueryRoute(config)
    callActions('request', config)
    trackLoading(config, 1)
    return config
}, (error) => {
    callActions('error_request', error)
    callActions('error', error)
    return Promise.reject(error)
})

http.interceptors.response.use((response) => {
    callActions('response', response)
    trackLoading(response.config, -1)
    return response
}, (error) => {
    if (error.config) {
        trackLoading(error.config, -1)
    }

    const normalized = normalizeApiError(error)

    callActions('error_response', normalized)
    callActions('error', normalized)

    return Promise.reject(normalized)
})

export default http

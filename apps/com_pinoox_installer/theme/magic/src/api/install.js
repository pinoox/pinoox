import {http} from '@global'
import {isApiEnvelope, unwrapApiResponse, ApiClientError} from '@/utils/apiEnvelope.js'

const unwrap = (response) => unwrapApiResponse(response)

const unwrapSetup = (response) => {
    const body = response?.data

    if (!isApiEnvelope(body)) {
        throw new ApiClientError('Invalid installer response', {
            code: 'INVALID_SETUP_RESPONSE',
            status: response?.status ?? null,
        })
    }

    return unwrapApiResponse(response)
}

export const installAPI = {
    changeLang: (lang) => http.get(`/changeLang/${lang}`).then(unwrap),

    agreement: () => http.get('/agreement').then(unwrap),

    ping: () => http.get('/ping', {loading: false}).then(unwrap),

    htaccessStatus: () => http.get('/htaccess/status', {loading: false}).then(unwrap),

    htaccessCreate: () => http.post('/htaccess/create', {}, {loading: false}).then(unwrap),

    checkPrerequisites: () => http.get('/checkPrerequisites', {loading: false}).then(unwrap),

    checkPrerequisite: (type) => http.get(`/checkPrerequisites/${type}`, {loading: false}).then(unwrap),

    checkDB: (params) => http.post('/checkDB', params, {loading: false}).then(unwrap),

    setup: (params) => http.post('/setup', params, {
        loading: false,
        timeout: 600000,
    }).then(unwrapSetup),
}

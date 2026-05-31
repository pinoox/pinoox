import {http} from '@global'

const unwrap = (response) => response.data

export const installAPI = {
    changeLang: (lang) => http.get(`changeLang/${lang}`).then(unwrap),

    agreement: () => http.get('agreement').then(unwrap),

    ping: () => http.get('ping', {loading: false}).then(unwrap),

    htaccessStatus: () => http.get('htaccess/status', {loading: false}).then(unwrap),

    htaccessCreate: () => http.post('htaccess/create', {}, {loading: false}).then(unwrap),

    checkPrerequisites: () => http.get('checkPrerequisites', {loading: false}).then(unwrap),

    checkPrerequisite: (type) => http.get(`checkPrerequisites/${type}`, {loading: false}).then(unwrap),

    checkDB: (params) => http.post('checkDB', params, {loading: false}).then(unwrap),

    setup: (params) => http.post('setup', params, {loading: false}).then(unwrap),
}

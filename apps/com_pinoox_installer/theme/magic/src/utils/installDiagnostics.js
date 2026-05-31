function hint(text, tool = null) {
    if (!text) {
        return null
    }

    return tool ? {text, tool} : {text}
}

function apiBaseUrl() {
    if (import.meta.env.MODE === 'production') {
        return typeof PINOOX !== 'undefined' ? PINOOX.URL?.API : ''
    }

    return import.meta.env.VITE_API_PATH ?? ''
}

export function isPinooxLoaded() {
    return typeof PINOOX !== 'undefined' && PINOOX?.URL?.API
}

export function diagnoseBootstrapError(LANG) {
    const install = LANG?.install ?? {}

    return {
        type: 'bootstrap',
        title: install.err_pinoox_missing_title ?? 'Unable to start installation',
        message: install.err_pinoox_missing_description ?? 'The installer could not connect to the server properly.',
        hints: [
            hint(install.err_connection_hint_rewrite),
            hint(install.err_connection_hint_htaccess, 'htaccess'),
            hint(install.err_connection_hint_pinoox_js),
        ].filter(Boolean),
        apiUrl: null,
    }
}

export function diagnoseApiError(error, LANG) {
    const install = LANG?.install ?? {}
    const apiUrl = apiBaseUrl()

    if (!isPinooxLoaded()) {
        return diagnoseBootstrapError(LANG)
    }

    const hints = [
        hint(install.err_connection_hint_rewrite),
        hint(install.err_connection_hint_htaccess, 'htaccess'),
        apiUrl && install.err_connection_hint_api_url
            ? hint(install.err_connection_hint_api_url.replace(':url', apiUrl))
            : null,
        hint(install.err_connection_hint_pinoox_js),
    ].filter(Boolean)

    if (!error?.response) {
        return {
            type: 'network',
            title: install.err_connection_title ?? 'Cannot connect to installer',
            message: install.err_connection_description ?? 'Could not reach the installer API.',
            hints,
            apiUrl,
            status: null,
        }
    }

    if (error.response.status === 404) {
        const hints404 = [
            hint(install.err_connection_hint_404 ?? install.err_connection_hint_rewrite),
            hint(install.err_connection_hint_htaccess, 'htaccess'),
            apiUrl && install.err_connection_hint_api_url
                ? hint(install.err_connection_hint_api_url.replace(':url', apiUrl))
                : null,
            hint(install.err_connection_hint_pinoox_js),
        ].filter(Boolean)

        return {
            type: 'routing',
            title: install.err_connection_title ?? 'Cannot connect to installer',
            message: install.err_connection_description ?? 'API route not found. URL rewrite may be disabled.',
            hints: hints404,
            apiUrl,
            status: 404,
        }
    }

    return {
        type: 'http',
        title: install.err_connection_title ?? 'Cannot connect to installer',
        message: install.err_connection_description ?? 'The server returned an unexpected response.',
        hints,
        apiUrl,
        status: error.response.status,
    }
}

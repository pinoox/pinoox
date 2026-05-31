export function resolveInstallerRoot() {
    let pathname = window.location.pathname

    if (/index\.php$/i.test(pathname)) {
        return pathname.replace(/index\.php$/i, '')
    }

    pathname = pathname.replace(/\/(lang|rules|prerequisites|db|setup|user)\/?$/i, '/')

    if (!pathname.endsWith('/')) {
        pathname += '/'
    }

    return pathname
}

export function resolveSiteEntryUrl() {
    return `${window.location.origin}${resolveInstallerRoot()}`
}

export function resolveQueryRouteUrl(routePath) {
    const path = routePath.startsWith('/') ? routePath : `/${routePath}`

    return `${resolveSiteEntryUrl()}?route=${encodeURIComponent(path)}`
}

export function shouldUseQueryRoute() {
    if (typeof PINOOX !== 'undefined' && PINOOX.URL?.API) {
        return false
    }

    if (import.meta.env.MODE === 'development' && import.meta.env.VITE_API_PATH) {
        return false
    }

    return true
}

export function resolveInstallerApiBase() {
    if (typeof PINOOX !== 'undefined' && PINOOX.URL?.API) {
        return PINOOX.URL.API.replace(/\/?$/, '/')
    }

    if (import.meta.env.MODE === 'development' && import.meta.env.VITE_API_PATH) {
        const devPath = import.meta.env.VITE_API_PATH

        if (devPath.startsWith('http')) {
            return devPath.replace(/\/?$/, '/')
        }

        return `${window.location.origin}${devPath.replace(/\/?$/, '/')}`
    }

    return resolveSiteEntryUrl()
}

export function resolveInstallerApiUrl(endpoint) {
    const clean = String(endpoint).replace(/^\//, '')

    if (typeof PINOOX !== 'undefined' && PINOOX.URL?.API) {
        const base = PINOOX.URL.API.replace(/\/?$/, '/')

        return `${base}${clean}`
    }

    return resolveQueryRouteUrl(`/${clean}`)
}

export function resolvePinooxJsUrl(useQueryRoute = false) {
    if (typeof PINOOX !== 'undefined' && PINOOX.URL?.BASE && !useQueryRoute) {
        return `${PINOOX.URL.BASE.replace(/\/?$/, '/')}dist/pinoox.js`
    }

    const root = resolveInstallerRoot()

    if (!useQueryRoute) {
        return `${window.location.origin}${root}dist/pinoox.js`
    }

    return resolveQueryRouteUrl('/dist/pinoox.js')
}

async function parseJson(response) {
    const data = await response.json().catch(() => ({}))

    if (!response.ok) {
        throw Object.assign(new Error('Request failed'), {response: {data, status: response.status}})
    }

    return data
}

export async function fetchBootstrapDiagnostics() {
    const response = await fetch(resolveInstallerApiUrl('bootstrap/diagnostics'))

    return parseJson(response)
}

export async function checkPinooxJsLoad(useQueryRoute = false) {
    const url = resolvePinooxJsUrl(useQueryRoute)

    try {
        const response = await fetch(url, {cache: 'no-store'})

        if (!response.ok) {
            return {ok: false, status: response.status, url, useQueryRoute}
        }

        const text = await response.text()
        const valid = /(?:const|var|let)\s+PINOOX\s*=/.test(text) || text.includes('PINOOX =')

        return {ok: valid, status: response.status, url, useQueryRoute}
    } catch {
        return {ok: false, status: null, url, useQueryRoute}
    }
}

export async function fetchHtaccessStatus() {
    const response = await fetch(resolveInstallerApiUrl('htaccess/status'))

    return parseJson(response)
}

export async function createHtaccessFile() {
    const response = await fetch(resolveInstallerApiUrl('htaccess/create'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
    })

    return parseJson(response)
}

export async function runBootstrapChecks() {
    const steps = {
        rewrite: {state: 'checking'},
        htaccess: {state: 'checking'},
        pinoox_js: {state: 'checking'},
    }

    try {
        const data = await fetchBootstrapDiagnostics()

        steps.rewrite = {...data.steps.rewrite}
        steps.htaccess = {...data.steps.htaccess}
        steps.pinoox_js = {...data.steps.pinoox_js}

        if (steps.pinoox_js.state === 'pending') {
            steps.pinoox_js = {...steps.pinoox_js, state: 'checking'}

            const pretty = await checkPinooxJsLoad(false)

            if (pretty.ok) {
                steps.pinoox_js = {state: 'pass', ...pretty}
            } else {
                steps.pinoox_js = {
                    state: 'fail',
                    ...pretty,
                    detail: pretty.status === 404 ? 'not_found' : 'invalid',
                }
            }
        }
    } catch {
        steps.rewrite = {state: 'fail', detail: 'api_unreachable'}
        steps.htaccess = {state: 'blocked', blocked_by: 'requires_rewrite'}
        steps.pinoox_js = {state: 'blocked', blocked_by: 'requires_rewrite'}
    }

    return steps
}

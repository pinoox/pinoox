import {unwrapApiBody} from '@/utils/apiEnvelope.js'

/** Pinoox query-route key (?_pnx=/path) — keep in sync with QueryRouteResolver::PARAMETER */
export const QUERY_ROUTE_PARAM = '_pnx'

let forceQueryRoute = false

export function setForceQueryRoute(value) {
    forceQueryRoute = Boolean(value)
}

export function isForceQueryRoute() {
    return forceQueryRoute
}

export function syncBootstrapQueryRoute(bootstrapError) {
    setForceQueryRoute(Boolean(bootstrapError))
}

function normalizeInstallerBasePath(base) {
    if (!base || typeof base !== 'string') {
        return null
    }

    let pathname = base

    if (/^https?:\/\//i.test(pathname)) {
        try {
            pathname = new URL(pathname).pathname
        } catch {
            return null
        }
    }

    if (/index\.php$/i.test(pathname)) {
        pathname = pathname.replace(/index\.php$/i, '')
    }

    if (!pathname.startsWith('/')) {
        pathname = `/${pathname}`
    }

    if (!pathname.endsWith('/')) {
        pathname += '/'
    }

    return pathname
}

export function resolveInstallerRoot() {
    if (typeof PINOOX !== 'undefined' && PINOOX.URL?.BASE) {
        const fromConfig = normalizeInstallerBasePath(PINOOX.URL.BASE)

        if (fromConfig) {
            return fromConfig
        }
    }

    let pathname = window.location.pathname

    if (/index\.php$/i.test(pathname)) {
        return pathname.replace(/index\.php$/i, '')
    }

    pathname = pathname.replace(/\/(lang|bootstrap|rules|prerequisites|db|setup|user)\/?$/i, '/')

    if (!pathname.endsWith('/')) {
        pathname += '/'
    }

    return pathname
}

function withInstallerBase(path) {
    if (!path || typeof path !== 'string') {
        return path
    }

    if (/^https?:\/\//i.test(path)) {
        return path
    }

    let normalized = path.startsWith('/') ? path : `/${path}`

    if (normalized.includes(`?${QUERY_ROUTE_PARAM}=`)) {
        const base = resolveInstallerRoot().replace(/\/$/, '')

        if (base && base !== '/' && !normalized.startsWith(`${base}/?`) && !normalized.startsWith(`${base}?`)) {
            normalized = `${base}${normalized}`
        }
    }

    return normalized
}

export function resolveSiteEntryUrl() {
    return `${window.location.origin}${resolveInstallerRoot()}`
}

export function resolveQueryRouteUrl(routePath) {
    const path = routePath.startsWith('/') ? routePath : `/${routePath}`

    return `${resolveSiteEntryUrl()}?${QUERY_ROUTE_PARAM}=${encodeURIComponent(path)}`
}

export function shouldUseQueryRoute() {
    if (forceQueryRoute) {
        return true
    }

    if (typeof PINOOX !== 'undefined' && PINOOX.URL?.API) {
        return false
    }

    if (import.meta.env.MODE === 'development' && import.meta.env.VITE_API_PATH) {
        return false
    }

    return true
}

export function resolveInstallerApiBase() {
    if (shouldUseQueryRoute()) {
        return resolveSiteEntryUrl()
    }

    return resolveDirectApiBase()
}

export function resolveDirectApiBase() {
    if (import.meta.env.MODE === 'development' && import.meta.env.VITE_API_PATH) {
        const devPath = import.meta.env.VITE_API_PATH

        if (devPath.startsWith('http')) {
            return devPath.replace(/\/?$/, '/')
        }

        const normalized = devPath.startsWith('/') ? devPath : `/${devPath}`

        return `${window.location.origin}${normalized.replace(/\/?$/, '/')}`
    }

    if (typeof PINOOX !== 'undefined' && PINOOX.URL?.API) {
        let api = PINOOX.URL.API.replace(/\/?$/, '/')

        if (/^https?:\/\//i.test(api)) {
            return api
        }

        api = withInstallerBase(api.startsWith('/') ? api : `/${api}`)

        return `${window.location.origin}${api.replace(/\/?$/, '/')}`
    }

    const root = resolveInstallerRoot()

    return `${window.location.origin}${root}api/v1/`
}

export function resolveInstallerApiUrl(endpoint, options = {}) {
    const clean = String(endpoint).replace(/^\//, '')

    if (clean === 'ping' && !options.allowQueryRoute) {
        return resolvePingApiUrl()
    }

    if (!shouldUseQueryRoute()) {
        const base = resolveDirectApiBase().replace(/\/?$/, '/')

        return `${base}${clean}`
    }

    return resolveQueryRouteUrl(`/${clean}`)
}

/** Always uses the clean URL — rewrite/htaccess must route /api/v1/ping directly. */
export function resolvePingApiUrl() {
    const root = resolveInstallerRoot().replace(/\/?$/, '')

    return `${window.location.origin}${root}/api/v1/ping`
}

export function resolvePinooxJsUrl(useQueryRoute = false) {
    const useQuery = useQueryRoute || shouldUseQueryRoute()

    if (typeof PINOOX !== 'undefined' && PINOOX.URL?.BASE && !useQuery) {
        return `${PINOOX.URL.BASE.replace(/\/?$/, '/')}dist/pinoox.js`
    }

    const root = resolveInstallerRoot()

    if (!useQuery) {
        return `${window.location.origin}${root}dist/pinoox.js`
    }

    return resolveQueryRouteUrl('/dist/pinoox.js')
}

async function parseJson(response) {
    const body = await response.json().catch(() => ({}))

    if (!response.ok) {
        throw Object.assign(new Error('Request failed'), {response: {data: body, status: response.status}})
    }

    return unwrapApiBody(body, {status: response.status})
}

export async function pingInstallerApi() {
    try {
        const response = await fetch(resolvePingApiUrl(), {
            cache: 'no-store',
            credentials: 'same-origin',
        })

        if (!response.ok) {
            return {ok: false, routing: false, status: response.status}
        }

        const body = await response.json().catch(() => ({}))
        const data = unwrapApiBody(body, {status: response.status})

        return {
            ok: Boolean(data?.ok),
            routing: Boolean(data?.routing),
            status: response.status,
            data,
        }
    } catch {
        return {ok: false, routing: false, status: null}
    }
}

export function shouldShowBootstrapError(ping = null) {
    if (ping && !ping.ok) {
        return true
    }

    return typeof PINOOX === 'undefined'
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

function mapHtaccessStep(status) {
    if (!status) {
        return {state: 'fail', detail: 'api_unreachable'}
    }

    const exists = Boolean(status.exists)
    const empty = Boolean(status.empty ?? true)
    const hasPinoox = Boolean(status.has_pinoox)

    if (exists && !empty && hasPinoox) {
        return {
            state: 'pass',
            exists: true,
            empty: false,
            has_pinoox: true,
            writable: Boolean(status.writable),
            can_create: Boolean(status.can_create),
            detail: 'ok',
        }
    }

    if (!exists || empty) {
        return {
            state: 'fail',
            exists,
            empty,
            has_pinoox: hasPinoox,
            writable: Boolean(status.writable),
            can_create: Boolean(status.can_create),
            detail: !exists ? 'missing' : 'empty',
        }
    }

    return {
        state: 'fail',
        exists,
        empty,
        has_pinoox: hasPinoox,
        writable: Boolean(status.writable),
        can_create: Boolean(status.can_create),
        detail: 'no_pinoox_block',
    }
}

async function fetchRewriteStepSafe() {
    try {
        const data = await fetchBootstrapDiagnostics()

        return {...data.steps.rewrite}
    } catch {
        return {state: 'fail', detail: 'api_unreachable'}
    }
}

async function fetchHtaccessStepSafe() {
    try {
        return mapHtaccessStep(await fetchHtaccessStatus())
    } catch {
        return {state: 'fail', detail: 'api_unreachable'}
    }
}

export async function runBootstrapChecksSteps12() {
    const [rewrite, htaccess] = await Promise.all([
        fetchRewriteStepSafe(),
        fetchHtaccessStepSafe(),
    ])

    return {rewrite, htaccess}
}

function isStepPassed(step) {
    return step?.state === 'pass'
}

export function shouldCheckStep3(steps12, ping) {
    if (!isStepPassed(steps12?.rewrite) || !isStepPassed(steps12?.htaccess)) {
        return false
    }

    return !ping?.ok || typeof PINOOX === 'undefined'
}

export function isBootstrapReady(ping, results) {
    if (!ping?.ok) {
        return false
    }

    if (!isStepPassed(results?.rewrite) || !isStepPassed(results?.htaccess)) {
        return false
    }

    if (typeof PINOOX === 'undefined' || shouldCheckStep3({rewrite: results.rewrite, htaccess: results.htaccess}, ping)) {
        return isStepPassed(results.pinoox_js)
    }

    return true
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

export async function runBootstrapCheckPinooxJs() {
    if (typeof PINOOX !== 'undefined' && !shouldUseQueryRoute()) {
        return {state: 'pass'}
    }

    const useQueryFirst = shouldUseQueryRoute()
    const primary = await checkPinooxJsLoad(useQueryFirst)

    if (primary.ok) {
        return {state: 'pass', ...primary}
    }

    if (!useQueryFirst) {
        const fallback = await checkPinooxJsLoad(true)

        if (fallback.ok) {
            return {state: 'pass', ...fallback}
        }

        const failed = primary.status !== null ? primary : fallback

        return {
            state: 'fail',
            ...failed,
            detail: failed.status === 404 ? 'not_found' : 'invalid',
        }
    }

    return {
        state: 'fail',
        ...primary,
        detail: primary.status === 404 ? 'not_found' : 'invalid',
    }
}

export async function runBootstrapChecks(preflightPing = null) {
    const steps12 = await runBootstrapChecksSteps12()
    const ping = preflightPing ?? await pingInstallerApi()

    const steps = {
        ...steps12,
        pinoox_js: shouldCheckStep3(steps12, ping)
            ? await runBootstrapCheckPinooxJs()
            : {state: 'blocked', blocked_by: 'pending'},
    }

    return steps
}
